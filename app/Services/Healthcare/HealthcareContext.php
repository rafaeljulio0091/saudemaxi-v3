<?php

namespace App\Services\Healthcare;

use App\Models\User;

class HealthcareContext
{
    /**
     * @return array<string, mixed>
     */
    public function forPatient(User $patient): array
    {
        $patient->loadMissing('tenant');
        abort_unless($patient->isPatient() && $patient->tenant, 403, 'Paciente sem vínculo com um cliente.');

        $tenant = $patient->tenant;

        return [
            'demo' => false,
            'profile' => 'patient',
            'tenant' => [
                'nome' => $tenant->name,
                'subdominio' => $tenant->slug.'.saudemaxi.com.br',
                'cor' => $tenant->brand_color ?: '#5E5212',
                'saudacao' => 'Cuidado e orientação para você.',
                'regulacao' => $tenant->regulacao,
            ],
            'patient' => [
                'id' => $patient->id,
                'nome' => $patient->name,
                'cpf' => $patient->cpf,
            ],
            'plan' => ['nome' => 'Saúde Maxi'],
            'modules' => [
                'orientacao' => true,
                'atendimento' => true,
                'agendamento' => true,
                'farmacia' => true,
                'nr1' => true,
            ],
            'network' => 'normal',
            'key' => "{$tenant->id}:patient:{$patient->id}",
            'basePath' => '',
            'apiBase' => '/triagem',
        ];
    }
}
