<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tablas adicionales: banco de preguntas y opciones de respuesta (catálogo de ítems de CU-02 y evaluaciones de RF-06). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id('id_pregunta');
            $table->foreignId('id_area')->constrained('areas_competencia', 'id_area')->restrictOnDelete();
            $table->foreignId('id_modulo')->nullable()->constrained('modulos_contenido', 'id_modulo')->restrictOnDelete();
            $table->enum('uso', ['diagnostico', 'evaluacion_modulo']);
            $table->unsignedTinyInteger('dificultad');
            $table->text('enunciado');
            $table->text('explicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignId('creado_por')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->timestamps();
            $table->index(['uso', 'id_area', 'dificultad', 'activo']);
        });

        Schema::create('opciones_respuesta', function (Blueprint $table) {
            $table->id('id_opcion');
            $table->foreignId('id_pregunta')->constrained('preguntas', 'id_pregunta')->cascadeOnDelete();
            $table->string('texto', 300);
            $table->boolean('es_correcta')->default(false);
            $table->unsignedTinyInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones_respuesta');
        Schema::dropIfExists('preguntas');
    }
};
