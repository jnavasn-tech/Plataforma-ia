<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas adicionales para RF-08: prompts institucionales versionados, bitácora de actividad
 * (citada en CU-01 y CU-06) y bitácora técnica de solicitudes a la IA (métricas de latencia y fallback).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_prompt', function (Blueprint $table) {
            $table->id('id_prompt');
            $table->enum('clave', ['diagnostico', 'ruta', 'retroalimentacion']);
            $table->unsignedSmallInteger('version');
            $table->text('contenido');
            $table->string('descripcion_cambio', 255)->nullable();
            $table->boolean('activo')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->timestamp('activado_en')->nullable();
            $table->timestamps();
            $table->unique(['clave', 'version']);
            $table->index(['clave', 'activo']);
        });

        Schema::create('bitacora', function (Blueprint $table) {
            $table->id('id_bitacora');
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('accion', 60);
            $table->string('entidad', 60)->nullable();
            $table->unsignedBigInteger('id_entidad')->nullable();
            $table->json('detalle_json')->nullable();
            $table->string('ip_anonimizada', 45)->nullable();
            $table->timestamp('fecha')->nullable();
            $table->index(['accion', 'fecha']);
        });

        Schema::create('bitacora_ia', function (Blueprint $table) {
            $table->id('id_bitacora_ia');
            $table->enum('tipo', ['diagnostico', 'ruta', 'retroalimentacion']);
            $table->enum('estado', ['exito', 'fallback', 'deshabilitada']);
            $table->string('modelo', 60)->nullable();
            $table->string('version_prompt', 20)->nullable();
            $table->unsignedInteger('latencia_ms')->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->unsignedInteger('tokens_entrada')->nullable();
            $table->unsignedInteger('tokens_salida')->nullable();
            $table->string('codigo_error', 60)->nullable();
            $table->string('referencia_tipo', 40)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->timestamp('fecha')->nullable();
            $table->index(['tipo', 'estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_ia');
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('configuraciones_prompt');
    }
};
