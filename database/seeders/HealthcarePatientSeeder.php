<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Attaches a real (non-demonstration) tenant, plan and patient profile to an
 * existing user so the /atendimento, /agendamento, /farmacia, /consultas and
 * /nr1 screens can be exercised end to end in a homologation environment.
 *
 * Not wired into DatabaseSeeder::run() on purpose: real patient onboarding
 * (who becomes a tenant, which plan they contract) is a product decision
 * that has not been made yet (see the completion report). Run manually:
 *
 *   php artisan db:seed --class=Database\\Seeders\\HealthcarePatientSeeder
 */
class HealthcarePatientSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command?->warn('Nenhum usuário encontrado. Crie uma conta antes de rodar este seeder.');

            return;
        }

        if ($user->patient) {
            $this->command?->warn("O usuário {$user->email} já tem um perfil de paciente.");

            return;
        }

        $tenant = Tenant::firstOrCreate(
            ['subdominio' => 'homologacao.saudemaxi.com.br'],
            [
                'nome' => 'Ambiente de Homologação',
                'saudacao' => 'Bem-vindo ao ambiente de homologação da Saúde Maxi',
                'cor' => '#1D4E89',
                'regulacao' => true,
            ],
        );

        $plan = Plan::firstOrCreate(
            ['tenant_id' => $tenant->id, 'nome' => 'Plano de homologação'],
            [
                'max_dependentes' => 0,
                'modules' => [
                    'orientacao' => true,
                    'atendimento' => true,
                    'agendamento' => true,
                    'farmacia' => true,
                    'nr1' => true,
                ],
            ],
        );

        Patient::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'nome' => $user->name,
            'email' => $user->email,
            'titular' => true,
            'status' => 'ACTIVE',
        ]);

        $this->command?->info("Perfil de paciente criado para {$user->email} no tenant {$tenant->nome}.");
    }
}
