<?php

/*
|--------------------------------------------------------------------------
| Integración con el proveedor de IA (actor externo "API OpenAI")
|--------------------------------------------------------------------------
| La clave nunca se escribe en el código: se lee de variables de entorno.
| El modelo, la temperatura, el tiempo de espera y los reintentos son
| configurables, de modo que puede cambiarse de modelo sin modificar código.
*/

return [
    'habilitada' => (bool) env('IA_HABILITADA', true),

    'proveedor' => env('IA_PROVEEDOR', 'openai'),

    'openai' => [
        'url_base' => env('OPENAI_URL_BASE', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'organizacion' => env('OPENAI_ORGANIZACION'),
    ],

    'modelo' => env('IA_MODELO', 'gpt-4o-mini'),

    // Temperatura baja para favorecer consistencia de la salida estructurada (ver hallazgo H-15).
    'temperatura' => (float) env('IA_TEMPERATURA', 0.3),

    'max_tokens' => (int) env('IA_MAX_TOKENS', 1200),

    // Tiempo máximo de espera por solicitud, en segundos.
    'timeout' => (int) env('IA_TIMEOUT', 15),

    // Reintentos ante errores transitorios (conexión, 429 y 5xx) y espera entre ellos (ms).
    'reintentos' => (int) env('IA_REINTENTOS', 1),
    'espera_reintento_ms' => (int) env('IA_ESPERA_REINTENTO_MS', 500),
];
