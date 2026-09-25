<?php

namespace App\Services\Healthcare;

use App\Models\Prescription;
use Illuminate\Support\Str;

class HealthcareDataService
{
    /**
     * Actions that depend on the external telemedicine provider integration
     * (see AGENTS.md §12). No service token or contract exists yet, so these
     * report an explicit, honest "not configured" failure instead of
     * fabricating specialties, schedules or a fake appointment.
     */
    private const PENDING_INTEGRATION = [
        'specialties' => 'A busca de especialidades depende da integração com a plataforma de atendimento, ainda não configurada para esta conta.',
        'days' => 'A consulta de dias disponíveis depende da integração com a plataforma de atendimento, ainda não configurada para esta conta.',
        'times' => 'A consulta de horários disponíveis depende da integração com a plataforma de atendimento, ainda não configurada para esta conta.',
        'doctors' => 'A busca de profissionais depende da integração com a plataforma de atendimento, ainda não configurada para esta conta.',
        'schedule' => 'A criação de agendamentos depende da integração com a plataforma de atendimento, ainda não configurada para esta conta.',
        'emergency' => 'O atendimento imediato depende da integração com a plataforma de atendimento, ainda não configurada para esta conta.',
        'max' => 'O assistente MAX ainda não está configurado para esta conta.',
    ];

    public function __construct(private PatientContext $context) {}

    public function read(string $resource, ?string $id = null): mixed
    {
        if (array_key_exists($resource, self::PENDING_INTEGRATION)) {
            abort(503, self::PENDING_INTEGRATION[$resource]);
        }

        return match ($resource) {
            'prescriptions' => $this->context->current()->prescriptions()
                ->latest()
                ->get()
                ->map(fn (Prescription $prescription) => $this->presentPrescription($prescription))
                ->all(),
            default => abort(404),
        };
    }

    public function execute(string $operation, array $input): mixed
    {
        if (array_key_exists($operation, self::PENDING_INTEGRATION)) {
            abort(503, self::PENDING_INTEGRATION[$operation]);
        }

        return match ($operation) {
            'consultations-search' => $this->searchConsultations($input),
            'photo' => $this->storePrescriptionPhoto($input),
            default => abort(404),
        };
    }

    private function searchConsultations(array $input): array
    {
        $query = $this->context->current()->consultations()->getQuery();
        $search = Str::lower(Str::ascii($input['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->whereRaw('lower(codigo) like ?', ["%{$search}%"])
                    ->orWhereRaw('lower(especialidade) like ?', ["%{$search}%"])
                    ->orWhereRaw('lower(medico) like ?', ["%{$search}%"]);
            });
        }
        if (! empty($input['status'])) {
            $query->where('status', $input['status']);
        }
        $total = (clone $query)->count();
        $page = max(1, (int) ($input['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($input['per_page'] ?? 10)));
        $results = $query->orderByDesc('agendada_para')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($consultation) => $this->presentConsultation($consultation))
            ->all();

        return ['count' => $total, 'results' => $results, 'page' => $page, 'per_page' => $perPage];
    }

    private function storePrescriptionPhoto(array $input): array
    {
        $prescription = $this->context->current()->prescriptions()->create([
            'status' => 'AGUARDANDO_ANALISE',
            'medico' => null,
            'itens' => [],
            'photo_path' => $input['photo_path'],
        ]);

        return $this->presentPrescription($prescription);
    }

    private function presentConsultation($consultation): array
    {
        return [
            'codigo' => $consultation->codigo,
            'especialidade' => $consultation->especialidade,
            'medico' => $consultation->medico,
            'status' => $consultation->status,
            'agendadaPara' => $consultation->agendada_para->toIso8601String(),
            'pago' => $consultation->pago,
            'duracao' => $consultation->duracao,
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
