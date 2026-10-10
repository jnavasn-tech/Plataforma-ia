<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla original "diagnosticos" (sesiones de diagnóstico) y tabla adicional "respuestas_diagnostico".
 * El atributo original respuestas_json se normaliza en respuestas_diagnostico para garantizar integridad
 * referencial, reanudación (CU-02 flujo alterno) y análisis por reactivo (RNF-02). RF-02, CU-02.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id('id_diagnostico');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $table->foreignId('id_proceso')->nullable()->constrained('procesos_seleccion', 'id_proceso')->nullOnDelete();
            $table->enum('tipo', ['pretest', 'postest'])->default('pretest');
            $table->enum('estado', ['iniciado', 'en_progreso', 'completado', 'abandonado'])->default('iniciado');
            $table->unsignedSmallInteger('total_items');
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('ultima_actividad_en')->nullable();
            $table->timestamp('finalizado_en')->nullable()->index();
            $table->timestamps();
            $table->index(['id_usuario', 'tipo', 'estado']);
        });

        Schema::create('respuestas_diagnostico', function (Blueprint $table) {
            $table->id('id_respuesta');
            $table->foreignId('id_diagnostico')->constrained('diagnosticos', 'id_diagnostico')->cascadeOnDelete();
            $table->foreignId('id_pregunta')->constrained('preguntas', 'id_pregunta')->restrictOnDelete();
            $table->foreignId('id_opcion')->nullable()->constrained('opciones_respuesta', 'id_opcion')->restrictOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->unsignedTinyInteger('dificultad');
            $table->boolean('es_correcta')->nullable();
            $table->timestamp('presentada_en')->nullable();
            $table->timestamp('respondida_en')->nullable();
            $table->unsignedInteger('tiempo_respuesta_ms')->nullable();
            $table->unsignedInteger('tiempo_servidor_ms')->nullable();
            $table->timestamps();
            $table->unique(['id_diagnostico', 'id_pregunta']);
            $table->unique(['id_diagnostico', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_diagnostico');
        Schema::dropIfExists('diagnosticos');
    }
};
