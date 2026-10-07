<?php

namespace App\Services\Healthcare;

use App\Models\Prescription;
use App\Models\User;
use App\Services\Telemedicine\LsxMedicalConsultationClient;
use App\Services\Telemedicine\TelemedicineApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class HealthcareDataService
{
    /**
     * Actions that depend on a provider capability that has no client in this
     * codebase yet. Building one would mean guessing an
     * undocumented contract, which AGENTS.md §12/§25 rules out. These report
     * an explicit, honest "not configured" failure instead.
     */
    private const PENDING_INTEGRATION = [
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

    /**
     * Manager lookup keeps patient CPF in an authenticated POST body. Tenant
     * access is established locally before the server-side provider call.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function searchConsultationsForManager(User $manager, array $input): array
    {
        $manager->loadMissing('tenant');
        abort_unless($manager->isManager() && $manager->tenant, 403, 'Gestor sem vínculo com um cliente.');

        if (empty($input['search'])) {
            return ['count' => 0, 'results' => [], 'page' => 1, 'per_page' => 0];
        }

        $page = (int) ($input['page'] ?? 1);

        try {
            $result = $this->consultations->search([
                'cpf' => $input['search'],
                'status' => $input['status'] ?? null,
                'doctor_cpf' => $input['doctor_cpf'] ?? null,
                'start_date_min' => $input['start_date_min'] ?? null,
                'start_date_max' => $input['start_date_max'] ?? null,
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
        /** @var UploadedFile $file */
        $file = $input['file'];
        $photoPath = $file->store('prescriptions/'.$user->id, 'local');

        if ($photoPath === false) {
            throw new RuntimeException('Não foi possível armazenar a receita.');
        }

        try {
            $prescription = $user->prescriptions()->create([
                'status' => 'AGUARDANDO_ANALISE',
                'medico' => null,
                'itens' => [],
                'photo_path' => $photoPath,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($photoPath);

            throw $exception;
        }

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
