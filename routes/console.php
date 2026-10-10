<?php

use App\Enums\EstadoDiagnostico;
use App\Models\Diagnostico;
use Illuminate\Support\Facades\Artisan;

/*
| Marca como "abandonados" los diagnósticos sin actividad (diagrama de estados, Figura 12).
| Se programa diariamente en app/Console/Kernel.php; si el programador no se ejecuta, la verificación
| también ocurre al reingresar el aspirante.
*/
Artisan::command('diagnosticos:marcar-abandonados', function () {
    $horas = (int) config('plataforma.diagnostico.horas_inactividad_abandono', 72);
    $total = Diagnostico::whereIn('estado', [EstadoDiagnostico::Iniciado->value, EstadoDiagnostico::EnProgreso->value])
        ->where('ultima_actividad_en', '<', now()->subHours($horas))
        ->update(['estado' => EstadoDiagnostico::Abandonado->value]);
    $this->info("Diagnósticos marcados como abandonados: {$total}");
})->purpose('Marca diagnósticos inactivos como abandonados');
