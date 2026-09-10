<?php

namespace App\Services\Healthcare;

use Illuminate\Support\Str;

class DemoHealthcareService
{
    public function __construct(private DemoContext $context, private DemoMaxService $max) {}

    public function read(string $resource, ?string $id = null): mixed
    {
        $data = $this->context->data();
        $context = $this->context->current();
        if (in_array($resource, ['patients', 'patient', 'plans', 'dashboard', 'integrations'], true)) {
            $this->context->authorize('manager');
        }

        $result = match ($resource) {
            'patients' => $this->context->patients(),
            'patient' => $this->context->patient((int) $id),
            'account' => $this->account(),
            'consultations' => $this->context->consultations(),
            'prescriptions' => $this->context->prescriptions(),
            'prescription' => collect($this->context->prescriptions())->firstWhere('id', $id) ?? abort(404),
            'pharmacies' => $this->pharmacies($data),
            'specialties' => $this->specialties(),
            'plans' => array_values(array_filter($data['PLANOS'], fn ($p) => $p['cliente'] === $context['tenant'])),
            'dashboard' => $this->dashboard($data),
            default => abort(404),
        };

        return $result;
    }

    private function account(): array
    {
        $this->context->authorize('patient');

        return $this->context->patient($this->context->current()['patient']);
    }

    private function pharmacies(array $data): array
    {
        $this->context->authorize('patient', 'farmacia');

        return $this->context->current()['tenant'] === array_key_first($data['CLIENTES']) ? $data['FARMACIAS'] : [];
    }

    private function dashboard(array $data): array
    {
        $consultations = collect($this->context->consultations());

        return [
            'patients' => count($this->context->patients()), 'consultations' => $consultations->count(),
            'scheduled' => $consultations->where('status', 'SCHEDULED')->count(),
            'unpaid' => $consultations->where('pago', false)->count(),
            'hours' => $data['CONSULTAS_POR_HORA'], 'days' => $data['CONSULTAS_POR_DIA'],
            'indicators' => $data['INDICADORES'], 'ages' => $data['FAIXA_ETARIA'],
        ];
    }

    private function authorizeScheduling(): void
    {
        $this->context->authorize('patient', 'agendamento');
        abort_if($this->context->props()['tenant']['regulacao'], 403, 'A marcação passa pelo núcleo de regulação.');
    }

    private function specialties(): array
    {
        $this->authorizeScheduling();

        return array_map(fn ($s) => ['id' => $s['id'], 'name' => $s['nome'], 'price' => $s['precoRede']], $this->context->data()['ESPECIALIDADES']);
    }

    private function days(int $specialty): array
    {
        abort_unless(collect($this->specialties())->contains('id', $specialty), 422, 'Especialidade indisponível.');
        $days = [];
        for ($i = 1; $i <= 21 && count($days) < 8; $i++) {
            $day = today()->addDays($i);
            if ($day->isWeekday()) {
                $days[] = $day->toDateString();
            }
        }

        return $days;
    }

    private function times(int $specialty, string $date): array
    {
        abort_unless(in_array($date, $this->days($specialty), true), 422, 'Escolha um dia disponível.');

        return ['08:00', '09:30', '10:00', '13:30', '14:00', '16:30'];
    }

    private function doctors(array $input): array
    {
        abort_unless(in_array($input['time'], $this->times($input['specialty_id'], $input['date']), true), 422, 'Escolha um horário disponível.');
        $specialty = collect($this->specialties())->firstWhere('id', $input['specialty_id']);
        $doctors = collect($this->context->data()['MEDICOS'])->filter(fn ($d) => $d['real'] && $d['especialidade'] === $specialty['name'])
            ->map(fn ($d) => ['id' => $d['id'], 'name' => $d['nome'], 'price' => $specialty['price'], 'is_real' => true])->values()->all();
        $doctors[] = ['id' => 0, 'name' => 'Primeiro profissional disponível', 'price' => $specialty['price'], 'is_real' => false];

        return $doctors;
    }

    public function execute(string $operation, array $input): mixed
    {
        $context = $this->context->current();
        if (in_array($operation, ['patients-search', 'consultations-search'], true)) {
            return $this->search($operation, $input);
        }
        if (in_array($operation, ['payment', 'plan', 'branding', 'create-patient'], true)) {
            $this->context->authorize('manager');
        }
        if (in_array($operation, ['days', 'times', 'doctors', 'schedule'], true)) {
            $this->authorizeScheduling();
        }
        if ($operation === 'days') {
            return $this->days($input['specialty_id']);
        }
        if ($operation === 'times') {
            return $this->times($input['specialty_id'], $input['date']);
        }
        if ($operation === 'doctors') {
            return $this->doctors($input);
        }
        if ($operation === 'max') {
            return $this->max->respond($input['message'], $this->context);
        }
        if ($operation === 'emergency') {
            $this->context->authorize('patient', 'atendimento');

            return ['simulated' => true, 'message' => 'Demonstração do encaminhamento. Nenhum atendimento foi aberto. A fila e a sala de vídeo ficam na plataforma de atendimento.'];
        }

        $data = $this->context->data();
        $result = ['simulated' => true];
        switch ($operation) {
            case 'schedule':
                $existing = collect($this->context->consultations())->firstWhere('request_id', $input['request_id']);
                if ($existing) {
                    return $existing;
                }
                $doctor = collect($this->doctors($input))->firstWhere('id', $input['doctor_id']);
                abort_unless($doctor, 422, 'Profissional indisponível.');
                $specialty = collect($this->specialties())->firstWhere('id', $input['specialty_id']);
                $result = [
                    'codigo' => 'DEMO-'.Str::upper(Str::random(8)), 'pacienteId' => $context['patient'],
                    'tipo' => 'SCHEDULED', 'especialidade' => $specialty['name'], 'medico' => $doctor['name'],
                    'status' => 'SCHEDULED', 'agendadaPara' => $input['date'].'T'.$input['time'].':00',
                    'pago' => false, 'duracao' => null, 'receita' => false, 'request_id' => $input['request_id'],
                ];
                array_unshift($data['CONSULTAS'], $result);
                break;
            case 'payment':
                abort_unless(collect($this->context->consultations())->contains('codigo', $input['code']), 404);
                foreach ($data['CONSULTAS'] as &$consultation) {
                    if ($consultation['codigo'] === $input['code']) {
                        $consultation['pago'] = (bool) $input['paid'];
                    }
                }
                unset($consultation);
                break;
            case 'patient':
                $this->context->patient($input['id']);
                $fields = ['nome', 'email', 'telefone'];
                if (isset($input['planoId'])) {
                    $this->context->authorize('manager');
                    $this->validatePlan($input['planoId'], $data);
                    $fields[] = 'planoId';
                }
                foreach ($data['PACIENTES'] as &$patient) {
                    if ($patient['id'] === (int) $input['id']) {
                        foreach ($fields as $field) {
                            if (array_key_exists($field, $input)) {
                                $patient[$field] = $field === 'planoId' ? (int) $input[$field] : ($input[$field] ?? '');
                            }
                        }
                        $result = $patient;
                    }
                }
                unset($patient);
                break;
            case 'create-patient':
                $this->validatePlan($input['planoId'], $data);
                $result = [
                    ...$input, 'planoId' => (int) $input['planoId'], 'id' => max(array_column($data['PACIENTES'], 'id')) + 1,
                    'cliente' => $context['tenant'], 'cpf' => '000.000.000-00', 'status' => 'ACTIVE',
                    'titular' => true, 'online' => false, 'tags' => [], 'adesao' => today()->toDateString(),
                    'dependentes' => 0, 'cidade' => '', 'estado' => '',
                ];
                $data['PACIENTES'][] = $result;
                break;
            case 'plan':
                $this->validatePlan($input['id'], $data);
                foreach ($data['PLANOS'] as &$plan) {
                    if ($plan['id'] === (int) $input['id']) {
                        $plan['modulos'][$input['module']] = (bool) $input['enabled'];
                        $result = $plan;
                    }
                }
                unset($plan);
                break;
            case 'branding':
                $data['CLIENTES'][$context['tenant']] = [...$data['CLIENTES'][$context['tenant']], ...$input];
                $result = $data['CLIENTES'][$context['tenant']];
                break;
            case 'photo':
                $this->context->authorize('patient', 'farmacia');
                $result = $this->context->fixtures()['RECEITAS'][1];
                $result['id'] = 'DEMO-'.Str::upper(Str::random(8));
                $result['pacienteId'] = $context['patient'];
                $result['data'] = today()->toDateString();
                array_unshift($data['RECEITAS'], $result);
                break;
            case 'confirm-item':
                $prescription = collect($this->context->prescriptions())->firstWhere('id', $input['id']);
                abort_unless($prescription && isset($prescription['itens'][$input['index']]), 404);
                foreach ($data['RECEITAS'] as &$prescription) {
                    if ($prescription['id'] === $input['id']) {
                        $prescription['itens'][$input['index']]['nome'] = $input['name'];
                        $prescription['itens'][$input['index']]['confirmed'] = true;
                        $result = $prescription;
                    }
                }
                unset($prescription);
                break;
            default:
                abort(404);
        }
        $this->context->save($data);

        return $result;
    }

    private function validatePlan(int $id, array $data): void
    {
        abort_unless(collect($data['PLANOS'])->contains(fn ($p) => $p['id'] === $id && $p['cliente'] === $this->context->current()['tenant']), 422, 'Plano indisponível neste cenário.');
    }

    private function search(string $operation, array $input): array
    {
        $patients = $operation === 'patients-search';
        if ($patients) {
            $this->context->authorize('manager');
        }
        $records = collect($patients ? $this->context->patients() : $this->context->consultations());
        $search = Str::lower(Str::ascii($input['search'] ?? ''));
        $records = $records->filter(function ($record) use ($patients, $input, $search) {
            $fields = $patients ? ['nome', 'cpf', 'email'] : ['codigo', 'especialidade', 'medico'];
            $text = collect($fields)->map(fn ($field) => $record[$field] ?? '')->implode(' ');

            return ($search === '' || Str::contains(Str::lower(Str::ascii($text)), $search))
                && (empty($input['status']) || $record['status'] === $input['status'])
                && (! $patients || empty($input['holder']) || $record['titular'] === ($input['holder'] === 'titular'))
                && (! $patients || empty($input['plan_id']) || $record['planoId'] === (int) $input['plan_id']);
        })->values();
        if ($this->context->current()['network'] === 'empty') {
            $records = collect();
        }

        return [
            'count' => $records->count(),
            'results' => $records->slice(($input['page'] - 1) * $input['per_page'], $input['per_page'])->values()->all(),
            'page' => $input['page'], 'per_page' => $input['per_page'],
        ];
    }
}
