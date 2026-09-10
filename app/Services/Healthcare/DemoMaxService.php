<?php

namespace App\Services\Healthcare;

use Illuminate\Support\Str;

class DemoMaxService
{
    public function respond(string $message, DemoContext $context): array
    {
        $text = Str::lower(Str::ascii($message));
        $has = fn (array $words) => Str::contains($text, $words);
        $profile = $context->current()['profile'];
        $rule = 'navigation';
        $tone = 'info';
        if ($has(['dor no peito', 'peito apertado', 'falta de ar', 'nao consigo respirar', 'desmaio', 'desmaiei', 'convuls', 'sangrando', 'boca torta', 'avc', 'infarto', 'me matar', 'suicid'])) {
            $rule = 'emergency';
            $tone = 'danger';
            $response = 'Ligue 192, SAMU, ou procure o pronto atendimento mais perto. Eu não avalio sinais de gravidade. Não espere atendimento por vídeo.';
            $actions = [['label' => 'Ligar 192, SAMU', 'path' => 'tel:192']];
        } elseif ($has(['mental', 'nr1', 'nr-1', 'psicossocial', 'depress', 'ansiedade'])) {
            $rule = 'privacy';
            $response = 'A trilha de saúde mental ainda não está disponível. O gestor poderá consultar informações agregadas do grupo, nunca dados individuais de saúde mental.';
            $actions = [];
        } elseif ($has(['medicament', 'receita', 'remedio', 'farmacia', 'trocar'])) {
            $rule = 'medication';
            $response = 'Eu não sugiro troca de medicamento. Essa decisão é do seu médico. Você pode conferir sua receita; a cobertura real ainda depende da lista oficial.';
            $actions = $profile === 'patient' && $context->props()['modules']['farmacia'] ? [['label' => 'Minhas receitas', 'path' => '/farmacia']] : [];
        } elseif ($has(['dor', 'febre', 'tosse', 'enjoo', 'nausea', 'tontura', 'vomit', 'sinto', 'estou mal'])) {
            $rule = 'referral';
            $response = 'Quem avalia o que você está sentindo é um profissional de saúde. Posso ajudar você a encontrar atendimento.';
            $actions = $profile === 'patient' ? [['label' => 'Ajuda imediata', 'path' => '/ajuda']] : [];
        } elseif ($profile === 'manager') {
            $response = 'Posso ajudar a consultar pacientes, acompanhar consultas, configurar os módulos dos planos e revisar a identidade visual.';
            $actions = [['label' => 'Pacientes', 'path' => '/gestor/pacientes'], ['label' => 'Consultas', 'path' => '/gestor/consultas'], ['label' => 'Planos e módulos', 'path' => '/gestor/planos']];
        } else {
            $response = 'Posso ajudar com suas consultas, seu plano e seu cadastro. A marcação com especialista segue as regras do seu contrato.';
            $actions = [['label' => 'Minhas consultas', 'path' => '/consultas'], ['label' => 'Minha conta', 'path' => '/conta']];
        }

        return ['text' => $response, 'tone' => $tone, 'actions' => $actions, 'trace' => [
            'id' => (string) Str::uuid(), 'rule' => $rule, 'version' => 'demo-1', 'at' => now()->toIso8601String(),
        ]];
    }
}
