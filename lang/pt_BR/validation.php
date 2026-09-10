<?php

return [
    'required' => 'Preencha o campo :attribute.',
    'string' => 'O campo :attribute deve conter um texto.',
    'email' => 'Informe um e-mail válido.',
    'lowercase' => 'Use letras minúsculas no campo :attribute.',
    'unique' => 'Este :attribute já está em uso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha atual está incorreta.',
    'integer' => 'Informe um número inteiro no campo :attribute.',
    'boolean' => 'Escolha uma opção válida para :attribute.',
    'in' => 'Escolha uma opção válida para :attribute.',
    'date_format' => 'Informe uma data ou horário válido no campo :attribute.',
    'before_or_equal' => 'O campo :attribute deve ser uma data até :date.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'uuid' => 'A identificação da solicitação é inválida. Atualize a página.',
    'prohibited' => 'O campo :attribute não pode ser informado.',
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
    ],
    'max' => [
        'string' => 'O campo :attribute deve ter no máximo :max caracteres.',
        'numeric' => 'O campo :attribute deve ser no máximo :max.',
    ],
    'password' => [
        'letters' => 'A senha deve incluir uma letra.',
        'mixed' => 'A senha deve incluir letras maiúsculas e minúsculas.',
        'numbers' => 'A senha deve incluir um número.',
        'symbols' => 'A senha deve incluir um símbolo.',
        'uncompromised' => 'Escolha outra senha para proteger sua conta.',
    ],
    'attributes' => [
        'name' => 'nome', 'nome' => 'nome', 'email' => 'e-mail',
        'password' => 'senha', 'current_password' => 'senha atual',
        'password_confirmation' => 'confirmação da senha',
        'telefone' => 'telefone', 'nascimento' => 'nascimento',
        'scenario' => 'cenário', 'profile' => 'perfil', 'network' => 'estado dos serviços',
        'plan_id' => 'plano', 'planoId' => 'plano', 'page' => 'página',
        'per_page' => 'registros por página', 'date' => 'data', 'time' => 'horário',
    ],
];
