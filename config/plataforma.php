<?php

/*
|--------------------------------------------------------------------------
| Parámetros funcionales de la plataforma
|--------------------------------------------------------------------------
| Valores de la línea base técnica del Capítulo V. Deben validarse con los
| expertos y con Grupo Ilimitado, S. A. antes de la validación con usuarios.
*/

return [
    // Diagnóstico (RF-02, CU-02): 6 ítems por cada una de las 5 áreas = 30 ítems.
    'diagnostico' => [
        'items_por_area' => (int) env('DIAGNOSTICO_ITEMS_POR_AREA', 6),
        // Horas de inactividad tras las cuales un diagnóstico en progreso pasa a "abandonado".
        'horas_inactividad_abandono' => (int) env('DIAGNOSTICO_HORAS_ABANDONO', 72),
    ],

    // Clasificación del perfil (RF-03). Básico < 50; intermedio 50-79.99; avanzado >= 80.
    'niveles' => [
        'umbral_intermedio' => (float) env('NIVEL_UMBRAL_INTERMEDIO', 50),
        'umbral_avanzado' => (float) env('NIVEL_UMBRAL_AVANZADO', 80),
    ],

    // Rutas de aprendizaje (RF-04, CU-03).
    'rutas' => [
        'max_modulos' => (int) env('RUTA_MAX_MODULOS', 10),
        // Si la ruta no se ha generado (cola detenida) se aplica el fallback por reglas.
        'espera_maxima_segundos' => (int) env('RUTA_ESPERA_MAXIMA_SEGUNDOS', 120),
    ],

    // Evaluación por módulo (RF-06, CU-04).
    'evaluacion' => [
        'umbral_aprobacion' => (float) env('EVALUACION_UMBRAL', 70),
        'max_intentos' => (int) env('EVALUACION_MAX_INTENTOS', 3),
    ],

    // Catálogo de recursos (CU-07).
    'recursos' => [
        'verificar_url' => (bool) env('RECURSOS_VERIFICAR_URL', true),
        'timeout_verificacion_segundos' => (int) env('RECURSOS_TIMEOUT_VERIFICACION', 10),
    ],

    // Aviso de privacidad vigente aceptado en el registro (Anexo T).
    'privacidad' => [
        'version_aviso' => env('PRIVACIDAD_VERSION_AVISO', '1.0'),
        'edad_minima' => 17,
        'edad_maxima' => 25,
    ],
];
