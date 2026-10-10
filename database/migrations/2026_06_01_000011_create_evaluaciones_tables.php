<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas originales "evaluaciones" (id_evaluacion, id_modulo, id_usuario, calificacion, fecha) y
 * "retroalimentaciones" (id_retro, id_evaluacion, texto_generado, fecha), ampliadas con intento, aprobación,
 * origen y versión del prompt; tabla adicional "valoraciones_modulo" (opinión al final de cada módulo).
 * RF-06, RF-07, CU-04.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id('id_evaluacion');
            $table->foreignId('id_modulo')->constrained('modulos_contenido', 'id_modulo')->restrictOnDelete();
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $table->foreignId('id_ruta_modulo')->constrained('ruta_modulos', 'id_ruta_modulo')->cascadeOnDelete();
            $table->unsignedTinyInteger('intento');
            $table->decimal('calificacion', 5, 2);
            $table->boolean('aprobado');
            $table->json('respuestas_json');
            $table->timestamp('fecha')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['id_ruta_modulo', 'intento']);
            $table->index(['id_usuario', 'id_modulo']);
        });

        Schema::create('retroalimentaciones', function (Blueprint $table) {
            $table->id('id_retro');
            $table->foreignId('id_evaluacion')->unique()->constrained('evaluaciones', 'id_evaluacion')->cascadeOnDelete();
            $table->text('texto_generado');
            $table->json('sugerencias_json')->nullable();
            $table->string('siguiente_paso', 300)->nullable();
            $table->enum('origen', ['ia', 'reglas']);
            $table->string('version_prompt', 20)->nullable();
            $table->timestamp('fecha')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('valoraciones_modulo', function (Blueprint $table) {
            $table->id('id_valoracion');
            $table->foreignId('id_ruta_modulo')->unique()->constrained('ruta_modulos', 'id_ruta_modulo')->cascadeOnDelete();
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $table->unsignedTinyInteger('puntuacion');
            $table->string('comentario', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valoraciones_modulo');
        Schema::dropIfExists('retroalimentaciones');
        Schema::dropIfExists('evaluaciones');
    }
};
