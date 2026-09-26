<?php

namespace App\Services\Healthcare;

use App\Models\Prescription;
use App\Models\User;
use App\Services\Telemedicine\LsxMedicalConsultationClient;
use App\Services\Telemedicine\TelemedicineApiException;

class HealthcareDataService
{
    /**
     * Actions that depend on a provider capability that has no client in this
     * codebase yet (specialties, availability, booking, immediate video
     * routing). Building one would mean guessing an
     * undocumented contract, which AGENTS.md §12/§25 rules out. These report
     * an explicit, honest "not configured" failure instead.
     */
    private const PENDING_INTEGRATION = [
        'specialties' => 'A busca de especialidades depende de uma integração que ainda não existe com a plataforma de atendimento.',
        'days' => 'A consulta de dias disponíveis depende de uma integração que ainda não existe com a plataforma de atendimento.',
        'times' => 'A consulta de horários disponíveis depende de uma integração que ainda não existe com a plataforma de atendimento.',
        'doctors' => 'A busca de profissionais depende de uma integração que ainda não existe com a plataforma de atendimento.',
        'schedule' => 'A criação de agendamentos depende de uma integração que ainda não existe com a plataforma de atendimento.',
        'emergency' => 'O encaminhamento por vídeo depende de uma integração que ainda não existe com a plataforma de atendimento.',
    ];

    public function __construct(
        private LsxMedicalConsultationClient $consultations,
        private TenantPlanService $plans,
    ) {}

    public function read(User $user, string $resource, ?string $id = null): mixed
    {
        if (array_key_exists($resource, self::PENDING_INTEGRATION)) {
            abort(503, self::PENDING_INTEGRATION[$resource]);
        }

        if ($resource === 'prescriptions') {
            $this->ensureModule($user, 'farmacia');
        }

        return match ($resource) {
            'account' => $this->account($user),
            'prescriptions' => $user->prescriptions()
                ->latest()
                ->get()
                ->map(fn (Prescription $prescription) => $this->presentPrescription($prescription))
                ->all(),
            default => abort(404),
        };
    }

    public function execute(User $user, string $operation, array $input): mixed
    {
        if (array_key_exists($operation, self::PENDING_INTEGRATION)) {
            abort(503, self::PENDING_INTEGRATION[$operation]);
        }

        if ($operation === 'photo') {
            $this->ensureModule($user, 'farmacia');
        }

        return match ($operation) {
            'consultations-search' => $this->searchConsultations($user, $input),
            'patient' => $this->updateAccount($user, $input),
            'photo' => $this->storePrescriptionPhoto($user, $input),
            default => abort(404),
        };
    }

    private function searchConsultations(User $user, array $input): array
    {
        $this->ensurePatientWithTenant($user);

        $page = (int) ($input['page'] ?? 1);

        try {
            $result = $this->consultations->search([
                'cpf' => $user->cpf,
                'status' => $input['status'] ?? null,
                'page' => $page,
            ]);
        } catch (TelemedicineApiException $e) {
            report($e);
            abort(503, $e->getMessage());
        }

        return [
            'count' => $result['count'],
            'results' => array_map(fn (array $row) => $this->presentConsultation($row), $result['results']),
            'page' => $page,
            'per_page' => count($result['results']),
        ];
    }

    private function account(User $user): array
    {
        $this->ensurePatientWithTenant($user);

        return $this->presentAccount($user);
    }

    /**
     * @param  array{nome: string, email: string, telefone: ?string}  $input
     */
    private function updateAccount(User $user, array $input): array
    {
        $this->ensurePatientWithTenant($user);

        $user->fill([
            'name' => $input['nome'],
            'email' => $input['email'],
            'phone' => $input['telefone'] ?? null,
        ]);

        // Same rule as ProfileController::update.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $this->presentAccount($user);
    }

    /**
     * Only the fields Patient/Account.vue shows. The CPF is left out on
     * purpose (data minimisation), and there is no local source for
     * dependents, so it is null instead of an invented count.
     */
    private function presentAccount(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'telefone' => $user->phone,
            'nascimento' => $user->birthdate?->format('Y-m-d'),
            'dependentes' => null,
        ];
    }

    /**
     * Server-side counterpart of the plan module gate PatientAreaController
     * applies to the pages.
     */
    private function ensureModule(User $user, string $module): void
    {
        $this->ensurePatientWithTenant($user);
        abort_unless($this->plans->effectivePlan($user->tenant)['modules'][$module] ?? false, 403, 'Este serviço não está incluído no seu plano.');
    }

    /**
     * Same tenant rule as HealthcareContext::forPatient for the pages.
     */
    private function ensurePatientWithTenant(User $user): void
    {
        $user->loadMissing('tenant');
        abort_unless($user->isPatient() && $user->tenant, 403, 'Paciente sem vínculo com um cliente.');
    }

    private function storePrescriptionPhoto(User $user, array $input): array
    {
        $prescription = $user->prescriptions()->create([
            'status' => 'AGUARDANDO_ANALISE',
            'medico' => null,
            'itens' => [],
            'photo_path' => $input['photo_path'],
        ]);

        return $this->presentPrescription($prescription);
    }

    /**
     * Maps the raw lsxmedical consultation-history row (fields verified
     * against Manager/Consultations.vue: code, start_date, specialty,
     * doctor_name, status) onto the shape Shared/Consultations.vue expects.
     * The provider's response has no payment field, so `pago` is left out
     * here; the page only shows that column when `context.demo` is true.
     */
    private function presentConsultation(array $row): array
    {
        return [
            'codigo' => $row['code'] ?? $row['id'] ?? '-',
            'especialidade' => $row['specialty'] ?? '-',
            'medico' => $row['doctor_name'] ?? $row['doctor_cpf'] ?? null,
            'status' => $row['status'] ?? 'PENDING',
            'agendadaPara' => $row['start_date'] ?? null,
            'pago' => null,
            'duracao' => null,
        ];
    }

    private function presentPrescription(Prescription $prescription): array
    {
        return [
            'id' => $prescription->id,
            'data' => $prescription->created_at->toDateString(),
            'medico' => $prescription->medico ?? 'Aguardando análise',
            'status' => $prescription->status,
            'itens' => $prescription->itens ?? [],
        ];
    }
}
