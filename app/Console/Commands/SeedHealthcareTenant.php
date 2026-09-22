<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Manual provisioning for a tenant (municipio/empresa) and its first
 * manager or patient, while there is no self-service onboarding flow yet.
 */
class SeedHealthcareTenant extends Command
{
    protected $signature = 'healthcare:seed-tenant
        {subdomain : Subdominio do tenant, ex: queimados}
        {name : Nome de exibicao, ex: "Prefeitura de Queimados"}
        {--type=municipio : municipio, empresa ou particular}
        {--regulacao : Marca que o agendamento passa pelo nucleo de regulacao}
        {--modules=orientacao,atendimento,farmacia : Lista de modulos habilitados, separados por virgula}
        {--brand-color= : Cor de marca em hexadecimal}
        {--manager-email= : Cria ou vincula um gestor com este e-mail}
        {--manager-password= : Senha do gestor, obrigatoria ao criar}
        {--manager-name= : Nome do gestor, obrigatorio ao criar}
        {--patient-email= : Cria ou vincula um paciente com este e-mail}
        {--patient-password= : Senha do paciente, obrigatoria ao criar}
        {--patient-name= : Nome do paciente, obrigatorio ao criar}
        {--patient-cpf= : CPF do paciente, necessario para falar com a plataforma real}';

    protected $description = 'Cria ou atualiza um tenant e, opcionalmente, seu primeiro gestor ou paciente';

    public function handle(): int
    {
        $modules = collect(explode(',', (string) $this->option('modules')))
            ->map(fn ($m) => trim($m))->filter()->values()->all();
        $enabledModules = array_fill_keys($modules, true);

        $tenant = Tenant::updateOrCreate(
            ['subdomain' => $this->argument('subdomain')],
            [
                'name' => $this->argument('name'),
                'type' => $this->option('type'),
                'regulacao' => (bool) $this->option('regulacao'),
                'brand_color' => $this->option('brand-color'),
                'modules' => $enabledModules,
            ],
        );
        $this->info("Tenant '{$tenant->name}' pronto em {$tenant->subdomain}.".config('healthcare.tenant_base_domain'));

        if ($this->option('manager-email')) {
            $this->provisionUser($tenant, 'manager', $this->option('manager-email'), $this->option('manager-password'), name: $this->option('manager-name'));
        }
        if ($this->option('patient-email')) {
            $this->provisionUser($tenant, 'patient', $this->option('patient-email'), $this->option('patient-password'), $this->option('patient-cpf'), $this->option('patient-name'));
        }

        return self::SUCCESS;
    }

    private function provisionUser(Tenant $tenant, string $role, string $email, ?string $password, ?string $cpf = null, ?string $name = null): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            $validator = Validator::make(compact('email', 'password', 'name'), [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8'],
                'name' => ['required', 'string', 'max:255'],
            ]);
            if ($validator->fails()) {
                $this->error("Não foi possível criar {$email}: ".$validator->errors()->first());

                return;
            }
            $user = new User(['name' => $name, 'email' => $email]);
            $user->password = $password;
        }
        $user->tenant_id = $tenant->id;
        $user->role = $role;
        if ($cpf) {
            $user->cpf = $cpf;
        }
        $user->save();
        $this->info("Usuário {$role} '{$email}' vinculado ao tenant '{$tenant->subdomain}'.");
    }
}
