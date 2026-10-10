<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Restricciones CHECK de integridad de dominio (MySQL 8.0.16 o superior las aplica).
 * En SQLite (pruebas automatizadas) se omiten porque no admite ALTER TABLE ... ADD CONSTRAINT;
 * las mismas reglas se validan en la capa de aplicación.
 */
return new class extends Migration
{
    private array $restricciones = [
        ['preguntas', 'chk_preguntas_dificultad', 'dificultad BETWEEN 1 AND 3'],
        ['respuestas_diagnostico', 'chk_respuestas_dificultad', 'dificultad BETWEEN 1 AND 3'],
        ['perfiles_competencia', 'chk_perfiles_puntaje', 'puntaje_global BETWEEN 0 AND 100'],
        ['evaluaciones', 'chk_evaluaciones_calificacion', 'calificacion BETWEEN 0 AND 100'],
        ['evaluaciones', 'chk_evaluaciones_intento', 'intento >= 1'],
        ['ruta_modulos', 'chk_ruta_modulos_avance', 'porcentaje_avance BETWEEN 0 AND 100'],
        ['valoraciones_modulo', 'chk_valoraciones_puntuacion', 'puntuacion BETWEEN 1 AND 5'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach ($this->restricciones as [$tabla, $nombre, $condicion]) {
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$nombre} CHECK ({$condicion})");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach ($this->restricciones as [$tabla, $nombre]) {
            DB::statement("ALTER TABLE {$tabla} DROP CHECK {$nombre}");
        }
    }
};
