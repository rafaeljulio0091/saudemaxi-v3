<?php

namespace App\Services\Healthcare;

use Illuminate\Http\Request;

class DemoContext
{
    private ?array $fixtureData = null;

    public function __construct(private Request $request) {}

    public function fixtures(): array
    {
        return $this->fixtureData ??= json_decode(file_get_contents(database_path('fixtures/healthcare-demo.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function select(array $input): void
    {
        $data = $this->data();
        $tenant = $input['scenario'];
        $patient = collect($data['PACIENTES'])->first(fn ($p) => $p['cliente'] === $tenant && $p['titular'] && $p['status'] === 'ACTIVE');
        $plan = collect($data['PLANOS'])->first(fn ($p) => $p['id'] === (int) ($input['plan_id'] ?? $patient['planoId']) && $p['cliente'] === $tenant);
        abort_unless($plan, 422, 'O plano não pertence ao cenário selecionado.');
        $this->request->session()->put('healthcare_demo.context', [
            'tenant' => $tenant, 'profile' => $input['profile'], 'patient' => $patient['id'],
            'plan' => $plan['id'], 'network' => $input['network'],
        ]);
    }

    public function data(): array
    {
        return $this->request->session()->has('healthcare_demo.data')
            ? $this->request->session()->get('healthcare_demo.data')
            : $this->fixtures();
    }

    public function save(array $data): void
    {
        $this->request->session()->put('healthcare_demo.data', $data);
    }

    public function current(): array
    {
        $context = $this->request->session()->get('healthcare_demo.context');
        abort_unless($context, 403, 'Selecione um cenário de demonstração.');

        return $context;
    }

    public function authorize(string $profile, ?string $module = null): void
    {
        $context = $this->current();
        abort_unless($context['profile'] === $profile, 403, 'Esta área pertence a outro perfil.');
        if ($module) {
            abort_unless($this->props()['modules'][$module] ?? false, 403, 'Este serviço não está incluído no plano.');
        }
    }

    public function patients(): array
    {
        $context = $this->current();

        return array_values(array_filter($this->data()['PACIENTES'], fn ($p) => $p['cliente'] === $context['tenant']));
    }

    public function patient(int $id): array
    {
        $patient = collect($this->patients())->firstWhere('id', $id);
        abort_unless($patient, 404);
        if ($this->current()['profile'] === 'patient') {
            abort_unless($id === $this->current()['patient'], 404);
        }

        return $patient;
    }

    public function consultations(): array
    {
        $context = $this->current();
        $ids = $context['profile'] === 'patient' ? [$context['patient']] : array_column($this->patients(), 'id');

        return array_values(array_filter($this->data()['CONSULTAS'], fn ($c) => in_array($c['pacienteId'], $ids, true)));
    }

    public function prescriptions(): array
    {
        $this->authorize('patient', 'farmacia');

        return array_values(array_filter($this->data()['RECEITAS'], fn ($r) => $r['pacienteId'] === $this->current()['patient']));
    }

    public function props(): array
    {
        $context = $this->current();
        $data = $this->data();
        $tenant = $data['CLIENTES'][$context['tenant']];
        $plan = collect($data['PLANOS'])->firstWhere('id', $context['plan']);
        $patient = collect($this->patients())->firstWhere('id', $context['patient']);

        return [
            'demo' => true, 'profile' => $context['profile'], 'tenant' => $tenant,
            'patient' => $context['profile'] === 'patient' ? $patient : null,
            'plan' => $plan, 'modules' => $plan['modulos'], 'network' => $context['network'],
            'key' => $context['tenant'].':'.$context['profile'].':'.$context['patient'].':'.$context['plan'],
            'basePath' => '/demonstracao', 'apiBase' => '/demonstracao/dados',
        ];
    }
}
