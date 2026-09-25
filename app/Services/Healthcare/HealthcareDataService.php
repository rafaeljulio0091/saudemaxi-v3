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
     * routing, the MAX assistant). Building one would mean guessing an
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
        'max' => 'O assistente MAX ainda não está configurado para esta conta.',
    ];

    public function __construct(private LsxMedicalConsultationClient $consultations) {}

    public function read(User $user, string $resource, ?string $id = null): mixed
    {
        if (array_key_exists($resource, self::PENDING_INTEGRATION)) {
            abort(503, self::PENDING_INTEGRATION[$resource]);
        }

        return match ($resource) {
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

        return match ($operation) {
            'consultations-search' => $this->searchConsultations($user, $input),
            'photo' => $this->storePrescriptionPhoto($user, $input),
            default => abort(404),
        };
    }

    private function searchConsultations(User $user, array $input): array
    {
        try {
            $result = $this->consultations->search([
                'cpf' => $user->cpf,
                'status' => $input['status'] ?? null,
                'page' => $input['page'] ?? 1,
            ]);
        } catch (TelemedicineApiException $e) {
            report($e);
            abort(503, $e->getMessage());
        }

        return [
            'count' => $result['count'],
            'results' => array_map(fn (array $row) => $this->presentConsultation($row), $result['results']),
            'page' => $input['page'] ?? 1,
            'per_page' => count($result['results']),
        ];
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
