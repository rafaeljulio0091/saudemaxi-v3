<?php

return [
    'max_message_length' => (int) env('TRIAGE_MAX_MESSAGE_LENGTH', 2000),
    'max_messages_per_session' => (int) env('TRIAGE_MAX_MESSAGES_PER_SESSION', 40),
    'context_message_limit' => (int) env('TRIAGE_CONTEXT_MESSAGE_LIMIT', 20),
    'min_confidence' => (float) env('TRIAGE_AI_MIN_CONFIDENCE', 0.85),
    'human_review_probability' => (float) env('TRIAGE_HUMAN_REVIEW_PROBABILITY', 0.50),
    'emergency_probability' => (float) env('TRIAGE_EMERGENCY_PROBABILITY', 0.50),
    'lock_seconds' => (int) env('TRIAGE_LOCK_SECONDS', 45),

    'safety' => [
        /*
        * IMPORTANTE:
        * Esta versão permanece pendente até revisão e aprovação
        * formal por responsável clínico.
        */
        'version' => env(
            'TRIAGE_SAFETY_RULE_VERSION',
            '2026-10-04-draft-pending-clinical-approval'
        ),

        /*
        * Estas regras NÃO realizam diagnóstico.
        *
        * Quando qualquer termo for detectado:
        * - interromper a triagem automática normal;
        * - classificar como safety escalation;
        * - não sugerir diagnóstico;
        * - não recomendar medicamentos;
        * - orientar atendimento emergencial;
        * - registrar qual regra foi acionada.
        */
        'rules' => [

            // ---------------------------------------------------------
            // CARDIORRESPIRATÓRIO
            // ---------------------------------------------------------
            [
                'id' => 'emergency-cardiorespiratory-v1',

                'terms_any' => [
                    'dor forte no peito',
                    'dor intensa no peito',
                    'dor súbita no peito',
                    'aperto no peito',
                    'pressão no peito',
                    'falta de ar intensa',
                    'não consigo respirar',
                    'não consegue respirar',
                    'dificuldade extrema para respirar',
                    'parou de respirar',
                    'não está respirando',
                    'lábios roxos',
                    'ficando roxo',
                    'cianose',
                ],
            ],

            // ---------------------------------------------------------
            // POSSÍVEIS SINAIS NEUROLÓGICOS / AVC
            // ---------------------------------------------------------
            [
                'id' => 'emergency-neurological-v1',

                'terms_any' => [
                    'rosto torto',
                    'boca torta',
                    'fala enrolada',
                    'dificuldade para falar',
                    'não consegue falar',
                    'fraqueza de um lado',
                    'paralisia de um lado',
                    'perdeu força de um lado',
                    'confusão mental súbita',
                    'perda súbita da visão',
                    'dor de cabeça súbita e intensa',
                ],
            ],

            // ---------------------------------------------------------
            // PERDA / ALTERAÇÃO IMPORTANTE DE CONSCIÊNCIA
            // ---------------------------------------------------------
            [
                'id' => 'emergency-consciousness-v1',

                'terms_any' => [
                    'desmaiou e não acorda',
                    'não responde',
                    'está inconsciente',
                    'ficou inconsciente',
                    'perdeu a consciência',
                    'não consegue acordar',
                    'não acorda',
                    'sem consciência',
                ],
            ],

            // ---------------------------------------------------------
            // CONVULSÃO
            // ---------------------------------------------------------
            [
                'id' => 'emergency-seizure-v1',

                'terms_any' => [
                    'está convulsionando',
                    'teve uma convulsão',
                    'crise convulsiva',
                    'convulsões repetidas',
                    'convulsionando',
                ],
            ],

            // ---------------------------------------------------------
            // HEMORRAGIA / TRAUMA GRAVE
            // ---------------------------------------------------------
            [
                'id' => 'emergency-bleeding-trauma-v1',

                'terms_any' => [
                    'sangramento intenso',
                    'sangramento que não para',
                    'hemorragia',
                    'perdendo muito sangue',
                    'traumatismo grave',
                    'trauma grave',
                    'acidente grave',
                    'atropelamento',
                    'ferimento por arma',
                ],
            ],

            // ---------------------------------------------------------
            // REAÇÃO ALÉRGICA GRAVE
            // ---------------------------------------------------------
            [
                'id' => 'emergency-allergic-reaction-v1',

                'terms_any' => [
                    'garganta fechando',
                    'garganta está fechando',
                    'língua inchando',
                    'língua inchada e falta de ar',
                    'inchaço na garganta',
                    'reação alérgica e falta de ar',
                    'alergia e não consigo respirar',
                ],
            ],

            // ---------------------------------------------------------
            // INTOXICAÇÃO / ENVENENAMENTO
            // ---------------------------------------------------------
            [
                'id' => 'emergency-poisoning-v1',

                'terms_any' => [
                    'tomei muitos remédios',
                    'tomou muitos remédios',
                    'overdose',
                    'envenenamento',
                    'ingeriu veneno',
                    'bebeu veneno',
                    'intoxicação grave',
                    'ingeriu produto químico',
                ],
            ],

            // ---------------------------------------------------------
            // RISCO DE AUTOAGRESSÃO / SUICÍDIO
            // ---------------------------------------------------------
            [
                'id' => 'emergency-self-harm-v1',

                'terms_any' => [
                    'quero me matar',
                    'vou me matar',
                    'quero tirar minha vida',
                    'vou tirar minha vida',
                    'tentei me matar',
                    'tentativa de suicídio',
                    'não quero mais viver',
                    'quero morrer',
                    'vou me machucar',
                    'quero me machucar',
                ],
            ],

            // ---------------------------------------------------------
            // OBSTÉTRICO COM POTENCIAL RISCO
            // ---------------------------------------------------------
            [
                'id' => 'emergency-obstetric-v1',

                'terms_any' => [
                    'grávida com sangramento intenso',
                    'gestante com sangramento intenso',
                    'grávida desmaiou',
                    'gestante desmaiou',
                    'grávida com convulsão',
                    'gestante com convulsão',
                    'grávida com falta de ar intensa',
                ],
            ],
        ],
    ],

    'emergency_contacts' => [
        ['label' => 'SAMU', 'phone' => '192'],
    ],
];
