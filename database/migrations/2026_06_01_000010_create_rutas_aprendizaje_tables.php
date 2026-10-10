<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla original "rutas_aprendizaje" (id_ruta, id_perfil, fecha_generacion, estado) ampliada con origen
 * (IA o reglas), narrativa y trazabilidad del modelo y del prompt; tabla adicional "ruta_modulos" (detalle de
 * la ruta) y "progreso_recursos" (citada en CU-04). RF-04, RF-05, CU-03, CU-04.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas_aprendizaje', function (Blueprint $table) {
            $table->id('id_ruta');
            $table->foreignId('id_perfil')->unique()->constrained('perfiles_competencia', 'id_perfil')->cascadeOnDelete();
            $table->enum('estado', ['activa', 'completada', 'reemplazada'])->default('activa')->index();
            $table->enum('origen', ['ia', 'reglas']);
            $table->text('narrativa');
            $table->json('fortalezas_json')->nullable();
            $table->json('areas_mejora_json')->nullable();
            $table->boolean('es_ruta_avanzada')->default(false);
            $table->string('modelo_ia', 60)->nullable();
            $table->string('version_prompt', 20)->nullable();
            $table->unsignedInteger('tiempo_generacion_ms')->nullable();
            $table->timestamp('fecha_generacion')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('completada_en')->nullable();
        });

        Schema::create('ruta_modulos', function (Blueprint $table) {
            $table->id('id_ruta_modulo');
            $table->foreignId('id_ruta')->constrained('rutas_aprendizaje', 'id_ruta')->cascadeOnDelete();
            $table->foreignId('id_modulo')->constrained('modulos_contenido', 'id_modulo')->restrictOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->enum('prioridad', ['alta', 'media', 'baja']);
            $table->string('justificacion', 500);
            $table->enum('estado', ['pendiente', 'en_curso', 'completado'])->default('pendiente');
            $table->decimal('porcentaje_avance', 5, 2)->default(0);
            $table->timestamp('completado_en')->nullable();
            $table->timestamps();
            $table->unique(['id_ruta', 'id_modulo']);
            $table->unique(['id_ruta', 'orden']);
        });

        Schema::create('progreso_recursos', function (Blueprint $table) {
            $table->id('id_progreso');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $table->foreignId('id_recurso')->constrained('recursos_aprendizaje', 'id_recurso')->cascadeOnDelete();
            $table->foreignId('id_ruta_modulo')->constrained('ruta_modulos', 'id_ruta_modulo')->cascadeOnDelete();
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('completado_en')->nullable();
            $table->timestamp('reportado_no_disponible_en')->nullable();
            $table->timestamps();
            $table->unique(['id_ruta_modulo', 'id_recurso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progreso_recursos');
        Schema::dropIfExists('ruta_modulos');
        Schema::dropIfExists('rutas_aprendizaje');
    }
};
