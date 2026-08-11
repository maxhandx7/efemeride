<?php

return [
    // URL interna de tu WAHA. En Coolify suele ser el nombre del servicio: http://waha:3000
    'base_url' => rtrim(env('WAHA_BASE_URL', 'http://waha:3000'), '/'),

    'api_key' => env('WAHA_API_KEY'),

    'session' => env('WAHA_SESSION', 'default'),

    // A donde llegan los avisos por defecto: tu propio numero.
    // Formato: 573001112233@c.us  (o un grupo: 1234567890-1600000000@g.us)
    'default_chat_id' => env('WAHA_DEFAULT_CHAT_ID'),

    // Prefijo pais para normalizar numeros escritos "a la colombiana" (3001112233)
    'country_code' => env('WAHA_COUNTRY_CODE', '57'),

    'timeout' => (int) env('WAHA_TIMEOUT', 20),

    // Simula que estas escribiendo antes de enviar. Detalle tonto, se siente humano.
    'simulate_typing' => (bool) env('WAHA_SIMULATE_TYPING', true),

    // Token que compartes con el webhook de WAHA para que nadie mas te hable
    'webhook_secret' => env('WAHA_WEBHOOK_SECRET'),

    // Numeros autorizados a dar ordenes por chat (sin sufijo, solo digitos)
    'admin_numbers' => array_filter(explode(',', (string) env('WAHA_ADMIN_NUMBERS', ''))),
];
