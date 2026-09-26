<?php

namespace App\Max\Support;

/**
 * The only links MAX may offer. The AI picks keys; the server resolves them
 * to paths, filtered by profile and by the modules of the patient's plan.
 */
class MaxActionCatalog
{
    private const ACTIONS = [
        'patient' => [
            'orientacao' => ['label' => 'Orientação em saúde', 'path' => '/orientacao', 'module' => 'orientacao'],
            'atendimento' => ['label' => 'Falar com um médico', 'path' => '/atendimento', 'module' => 'atendimento'],
            'agendamento' => ['label' => 'Agendar consulta', 'path' => '/agendamento', 'module' => 'agendamento'],
            'farmacia' => ['label' => 'Minhas receitas', 'path' => '/farmacia', 'module' => 'farmacia'],
            'consultas' => ['label' => 'Minhas consultas', 'path' => '/consultas', 'module' => null],
            'conta' => ['label' => 'Minha conta', 'path' => '/conta', 'module' => null],
            'ajuda' => ['label' => 'Ajuda imediata', 'path' => '/ajuda', 'module' => null],
        ],
        'manager' => [
            'painel' => ['label' => 'Painel', 'path' => '/gestor/painel', 'module' => null],
            'pacientes' => ['label' => 'Pacientes', 'path' => '/gestor/pacientes', 'module' => null],
            'consultas' => ['label' => 'Consultas', 'path' => '/gestor/consultas', 'module' => null],
            'planos' => ['label' => 'Planos e módulos', 'path' => '/gestor/planos', 'module' => null],
            'identidade' => ['label' => 'Identidade visual', 'path' => '/gestor/identidade', 'module' => null],
            'integracao' => ['label' => 'Integrações', 'path' => '/gestor/integracao', 'module' => null],
        ],
    ];

    /**
     * @param  array<string, bool>  $modules
     * @return list<string>
     */
    public function allowedKeys(string $profile, array $modules): array
    {
        return array_keys(array_filter(
            self::ACTIONS[$profile] ?? [],
            fn (array $action) => $action['module'] === null || ($modules[$action['module']] ?? false),
        ));
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, bool>  $modules
     * @return list<array{label: string, path: string}>
     */
    public function resolve(array $keys, string $profile, array $modules): array
    {
        $allowed = $this->allowedKeys($profile, $modules);

        return array_values(array_map(
            fn (string $key) => ['label' => self::ACTIONS[$profile][$key]['label'], 'path' => self::ACTIONS[$profile][$key]['path']],
            array_values(array_intersect(array_unique($keys), $allowed)),
        ));
    }

    /**
     * Page paths MAX may receive as context (no ids, no query strings).
     *
     * @return list<string>
     */
    public function knownPaths(string $profile): array
    {
        return [
            ...array_column(self::ACTIONS[$profile] ?? [], 'path'),
            $profile === 'manager' ? '/gestor/painel' : '/dashboard',
            '/profile',
        ];
    }
}
