<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Healthcare\ConsultationController;
use App\Http\Controllers\Healthcare\DemoController;
use App\Http\Controllers\Healthcare\HealthcareDataController;
use App\Http\Controllers\Healthcare\ManagerAreaController;
use App\Http\Controllers\Healthcare\ManagerDataController;
use App\Http\Controllers\Healthcare\MaxMessageController;
use App\Http\Controllers\Healthcare\PatientAreaController;
use App\Http\Controllers\Healthcare\PatientController;
use App\Http\Controllers\Healthcare\TriagePageController;
use App\Http\Controllers\Native\NativeRegistryController;
use App\Http\Controllers\Triage\TriageMessageController;
use App\Http\Controllers\Triage\TriageSessionController;
use App\Http\Middleware\EnsureHealthcareDemo;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$pages = [
    ['inicio', 'Patient/Home', 'Início', 'patient', null],
    ['orientacao', 'Patient/Guidance', 'Orientação em saúde', 'patient', 'orientacao'],
    ['atendimento', 'Patient/Immediate', 'Falar com um médico', 'patient', 'atendimento'],
    ['agendamento', 'Patient/Scheduling', 'Agendar consulta', 'patient', 'agendamento'],
    ['farmacia', 'Patient/Pharmacy', 'Farmácia popular', 'patient', 'farmacia'],
    ['receita/{id}', 'Patient/Prescription', 'Resultado da receita', 'patient', 'farmacia'],
    ['farmacias', 'Patient/Pharmacies', 'Farmácias próximas', 'patient', 'farmacia'],
    ['consultas', 'Shared/Consultations', 'Minhas consultas', 'patient', null],
    ['conta', 'Patient/Account', 'Minha conta', 'patient', null],
    ['nr1', 'Patient/MentalHealth', 'Saúde mental', 'patient', 'nr1'],
    ['ajuda', 'Patient/Help', 'Ajuda imediata', 'patient', null],
    ['gestor/painel', 'Manager/Dashboard', 'Painel de gestão', 'manager', null],
    ['gestor/pacientes', 'Manager/Patients', 'Pacientes', 'manager', null],
    ['gestor/pacientes/{id}', 'Manager/Patient', 'Ficha do paciente', 'manager', null],
    ['gestor/consultas', 'Shared/Consultations', 'Consultas', 'manager', null],
    ['gestor/planos', 'Manager/Plans', 'Planos e módulos', 'manager', null],
    ['gestor/identidade', 'Manager/Branding', 'Identidade visual', 'manager', null],
    ['gestor/integracao', 'Manager/Integrations', 'Integrações', 'manager', null],
];

// Paths already backed by a real (non-demonstration) implementation. Every
// other entry in $pages keeps rendering the 503 placeholder below until it
// gets the same treatment.
$readyPatientPaths = ['orientacao', 'atendimento', 'agendamento', 'farmacia', 'consultas', 'nr1', 'conta', 'ajuda'];
$readyManagerPaths = ['gestor/planos', 'gestor/identidade', 'gestor/integracao'];

foreach ($pages as [$path, $page, $title, $profile, $module]) {
    if (in_array($path, [...$readyPatientPaths, ...$readyManagerPaths, 'gestor/painel', 'gestor/pacientes', 'gestor/pacientes/{id}', 'gestor/consultas'], true)) {
        // These pages are registered below with a real implementation.
        continue;
    }

    Route::get($path, fn () => Inertia::render('Healthcare/NotReady')
        ->toResponse(request())->setStatusCode(503))->middleware('auth');
}

Route::get('orientacao', TriagePageController::class)
    ->middleware(['auth', 'verified', EnsureUserHasRole::class.':patient'])
    ->name('healthcare.patient.guidance');

foreach ($pages as [$path, $page, $title, $profile, $module]) {
    if (! in_array($path, ['atendimento', 'agendamento', 'farmacia', 'consultas', 'nr1', 'conta', 'ajuda'], true)) {
        continue;
    }

    Route::get($path, [PatientAreaController::class, 'page'])
        ->defaults('page', $page)->defaults('title', $title)->defaults('module', $module)
        ->middleware(['auth', 'verified', EnsureUserHasRole::class.':patient']);
}

Route::prefix('triagem')->middleware(['auth', 'verified'])->group(function () {
    Route::post('sessoes', [TriageSessionController::class, 'store'])
        ->middleware([EnsureUserHasRole::class.':patient', 'throttle:triage-start'])
        ->name('triage.sessions.store');
    Route::get('sessoes/{triageSession}', [TriageSessionController::class, 'show'])
        ->name('triage.sessions.show');
    Route::post('sessoes/{triageSession}/mensagens', [TriageMessageController::class, 'store'])
        ->middleware([EnsureUserHasRole::class.':patient', 'throttle:triage-messages'])
        ->block(5, 5)
        ->name('triage.messages.store');

    // MAX assistant (official). Registered before the generic operation route.
    Route::post('max', MaxMessageController::class)
        ->middleware([EnsureUserHasRole::class.':patient', 'throttle:max-messages'])
        ->name('max.patient');

    // Generic data endpoints for the real (non-demonstration) patient area:
    // /atendimento, /agendamento, /farmacia, /consultas and /nr1 all share
    // the same apiBase (see HealthcareContext::forPatient) already used by
    // the triage conversation above.
    Route::get('{resource}/{id?}', [HealthcareDataController::class, 'read'])
        ->where('resource', '(?!sessoes).*')
        ->middleware([EnsureUserHasRole::class.':patient', 'throttle:120,1,healthcare-read:']);
    Route::post('{operation}', [HealthcareDataController::class, 'execute'])
        ->where('operation', '(?!sessoes).*')
        ->middleware([EnsureUserHasRole::class.':patient', 'throttle:60,1,healthcare-operation:']);
});

Route::get('gestor/painel', [DashboardController::class, 'manager'])
    ->middleware(['auth', 'verified', EnsureUserHasRole::class.':manager'])
    ->name('healthcare.manager.dashboard');

Route::middleware(['auth', 'verified', EnsureUserHasRole::class.':manager'])->group(function () use ($pages, $readyManagerPaths) {
    foreach ($pages as [$path, $page, $title]) {
        if (in_array($path, $readyManagerPaths, true)) {
            Route::get($path, [ManagerAreaController::class, 'page'])
                ->defaults('page', $page)->defaults('title', $title);
        }
    }

    Route::get('gestor/pacientes', [ManagerAreaController::class, 'page'])
        ->defaults('page', 'Manager/Patients')->defaults('title', 'Pacientes')
        ->name('healthcare.manager.patients');
    Route::get('gestor/pacientes/{id}', [ManagerAreaController::class, 'page'])
        ->whereUuid('id')
        ->defaults('page', 'Manager/Patient')->defaults('title', 'Ficha do paciente');
    Route::post('gestor/pacientes', [PatientController::class, 'store'])
        ->middleware('throttle:sensitive-registry')
        ->name('healthcare.manager.patients.store');
    Route::post('gestor/dados/patients-search', [PatientController::class, 'search'])
        ->middleware('throttle:sensitive-registry');
    Route::post('gestor/dados/consultations-search', [ConsultationController::class, 'search'])
        ->middleware('throttle:sensitive-registry');
    Route::post('gestor/dados/create-patient', [PatientController::class, 'storeJson'])
        ->middleware('throttle:sensitive-registry');
    Route::post('gestor/dados/patient', [PatientController::class, 'updateFromPayload'])
        ->middleware('throttle:sensitive-registry');
    Route::get('gestor/dados/patient/{nativePatient}', [PatientController::class, 'show'])
        ->whereUuid('nativePatient');
    Route::patch('gestor/dados/patient/{nativePatient}', [PatientController::class, 'update'])
        ->whereUuid('nativePatient')->middleware('throttle:sensitive-registry');
    Route::delete('gestor/dados/patient/{nativePatient}', [PatientController::class, 'destroy'])
        ->whereUuid('nativePatient')->middleware('throttle:sensitive-registry');
    Route::post('gestor/dados/patient/{nativePatient}/restore', [PatientController::class, 'restore'])
        ->whereUuid('nativePatient')->middleware('throttle:sensitive-registry');

    Route::prefix('gestor/cadastros')->middleware('throttle:sensitive-registry')->group(function () {
        Route::post('municipios', [NativeRegistryController::class, 'municipality']);
        Route::post('organizacoes', [NativeRegistryController::class, 'organization']);
        Route::post('unidades-saude', [NativeRegistryController::class, 'healthUnit']);
        Route::post('farmacias', [NativeRegistryController::class, 'pharmacy']);
        Route::post('profissionais-saude', [NativeRegistryController::class, 'healthProfessional']);
    });

    // Data endpoints for the real manager screens (apiBase of
    // HealthcareContext::forManager), always scoped to the manager's tenant.
    // Specific sensitive routes above must be registered before these generic
    // operations so validation and policies cannot be bypassed.
    Route::get('gestor/dados/{resource}', [ManagerDataController::class, 'read'])
        ->middleware('throttle:120,1,manager-read:');
    Route::post('gestor/dados/max', MaxMessageController::class)
        ->middleware('throttle:max-messages')
        ->name('max.manager');
    Route::post('gestor/dados/{operation}', [ManagerDataController::class, 'execute'])
        ->middleware('throttle:60,1,manager-operation:');

    Route::get('gestor/consultas', [ManagerAreaController::class, 'page'])
        ->defaults('page', 'Shared/Consultations')->defaults('title', 'Consultas')
        ->name('healthcare.manager.consultations');
});

Route::prefix('demonstracao')->middleware(EnsureHealthcareDemo::class)->group(function () use ($pages) {
    Route::get('/', [DemoController::class, 'entry'])->name('healthcare.demo');
    Route::post('/cenario', [DemoController::class, 'select'])->middleware('throttle:30,1,healthcare-demo-scenario:')->block(10, 10);
    Route::get('/dados/{resource}/{id?}', [DemoController::class, 'read'])->middleware('throttle:120,1,healthcare-demo-read:');
    Route::post('/dados/{operation}', [DemoController::class, 'execute'])->middleware('throttle:60,1,healthcare-demo-operation:')->block(10, 10);
    foreach ($pages as [$path, $page, $title, $profile, $module]) {
        Route::get($path, [DemoController::class, 'page'])
            ->defaults('page', $page)->defaults('title', $title)
            ->defaults('profile', $profile)->defaults('module', $module);
    }
});
