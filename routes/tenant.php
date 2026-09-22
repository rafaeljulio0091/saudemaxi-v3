<?php

use App\Http\Controllers\Healthcare\PatientPortalController;
use Illuminate\Support\Facades\Route;

/*
 * Real (non-demo) tenant portal, resolved from the request subdomain
 * (see App\Http\Middleware\ResolveTenant). Only pages with a real backend
 * are wired to PatientPortalController; every other page from the
 * reference product still answers honestly with "NotReady" rather than
 * faking data (AGENTS.md section 25).
 */
Route::middleware(['tenant', 'auth', 'tenant.member'])->group(function () {
    Route::get('/inicio', [PatientPortalController::class, 'home']);
    Route::get('/atendimento', [PatientPortalController::class, 'immediate']);
    Route::get('/consultas', [PatientPortalController::class, 'consultations']);
    Route::get('/conta', [PatientPortalController::class, 'account']);

    foreach ([
        'orientacao', 'agendamento', 'farmacia', 'receita/{id}', 'farmacias', 'nr1', 'ajuda',
        'gestor/painel', 'gestor/pacientes', 'gestor/pacientes/{id}', 'gestor/consultas',
        'gestor/planos', 'gestor/identidade', 'gestor/integracao',
    ] as $path) {
        Route::get($path, [PatientPortalController::class, 'notReady']);
    }

    Route::prefix('api/healthcare')->group(function () {
        Route::get('/account', [PatientPortalController::class, 'apiAccount']);
        Route::post('/patient', [PatientPortalController::class, 'apiUpdatePatient']);
        Route::get('/consultations', [PatientPortalController::class, 'apiConsultations']);
        Route::post('/consultations-search', [PatientPortalController::class, 'apiConsultationsSearch']);
        Route::post('/emergency', [PatientPortalController::class, 'apiEmergency'])
            ->middleware('throttle:10,1');
    });
});
