<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla original "perfiles_competencia" (id_perfil, id_diagnostico, nivel_general, areas_evaluadas_json),
 * ampliada con puntaje global, interpretación, trazabilidad del prompt y nivel de la evaluación de control
 * (indicador de precisión del objetivo específico b). RF-03, CU-02.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_competencia', function (Blueprint $table) {
            $table->id('id_perfil');
            $table->foreignId('id_diagnostico')->unique()->constrained('diagnosticos', 'id_diagnostico')->cascadeOnDelete();
            $table->enum('nivel_general', ['basico', 'intermedio', 'avanzado']);
            $table->decimal('puntaje_global', 5, 2);
            $table->json('areas_evaluadas_json');
            $table->text('resumen')->nullable();
            $table->json('fortalezas_json')->nullable();
            $table->json('areas_mejora_json')->nullable();
            $table->text('recomendacion')->nullable();
            $table->enum('origen_interpretacion', ['ia', 'reglas'])->nullable();
            $table->string('version_prompt', 20)->nullable();
            $table->enum('nivel_control', ['basico', 'intermedio', 'avanzado'])->nullable();
            $table->foreignId('control_registrado_por')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->timestamp('control_registrado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_competencia');
    }
};
