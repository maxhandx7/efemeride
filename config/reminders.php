<?php

return [
    'timezone' => env('REMINDERS_TIMEZONE', 'America/Bogota'),

    // Antelaciones que se crean solas cuando guardas un evento sin elegir nada
    'default_days_before' => array_map('intval', explode(',', (string) env('REMINDERS_DEFAULT_DAYS', '7,1,0'))),

    'default_send_at' => env('REMINDERS_SEND_AT', '08:00'),

    // Que hacer con los del 29 de febrero en anios normales: 'february' (28) o 'march' (1 de marzo)
    'leap_day_fallback' => env('REMINDERS_LEAP_FALLBACK', 'february'),

    // Resumen semanal
    'digest' => [
        'enabled' => (bool) env('REMINDERS_DIGEST_ENABLED', true),
        'day' => env('REMINDERS_DIGEST_DAY', 'monday'),
        'at' => env('REMINDERS_DIGEST_AT', '07:30'),
        'horizon_days' => (int) env('REMINDERS_DIGEST_HORIZON', 30),
    ],

    // Mensajes escritos por IA (opcional). Si falla o no hay key, cae a la plantilla.
    'ai' => [
        'enabled' => (bool) env('REMINDERS_AI_ENABLED', false),

        // 'deepseek' (o cualquier API estilo OpenAI) o 'anthropic'
        'provider' => env('REMINDERS_AI_PROVIDER', 'deepseek'),

        'api_key' => env('REMINDERS_AI_KEY'),
        'model' => env('REMINDERS_AI_MODEL', 'deepseek-v4-flash'),
        'base_url' => rtrim(env('REMINDERS_AI_BASE_URL', 'https://api.deepseek.com'), '/'),

        // El thinking de DeepSeek V4 viene encendido de fabrica y se cobra.
        // Para un mensaje de dos frases no hace falta.
        'thinking' => (bool) env('REMINDERS_AI_THINKING', false),

        'tone' => env('REMINDERS_AI_TONE', 'calido, breve y con un toque de humor colombiano, sin exagerar'),
    ],

    'templates' => [
        'birthday' => [
            'before' => "🎂 Ojo pues: en {dias} dias es el cumpleanos de *{nombre}* ({fecha}).{edad_frase}",
            'today' => "🎂 Hoy cumple anios *{nombre}*.{edad_frase} No dejes que se te pase.",
        ],
        'anniversary' => [
            'before' => "💍 En {dias} dias es el aniversario de *{nombre}* ({fecha}).{edad_frase}",
            'today' => "💍 Hoy es el aniversario de *{nombre}*.{edad_frase}",
        ],
        'custom' => [
            'before' => "📌 Faltan {dias} dias para *{nombre}* ({fecha}).",
            'today' => "📌 Hoy es *{nombre}*.",
        ],
    ],
];
