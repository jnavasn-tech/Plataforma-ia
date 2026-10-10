<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tablas adicionales requeridas por CU-05 y CU-06: procesos de selección, postulaciones e historial de estados. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesos_seleccion', function (Blueprint $table) {
            $table->id('id_proceso');
            $table->string('nombre', 120);
            $table->string('puesto', 120);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id('id_postulacion');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $table->foreignId('id_proceso')->constrained('procesos_seleccion', 'id_proceso')->restrictOnDelete();
            $table->enum('estado', ['pendiente', 'en_formacion', 'apto', 'no_apto'])->default('pendiente')->index();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->timestamps();
            $table->unique(['id_usuario', 'id_proceso']);
        });

        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id('id_historial');
            $table->foreignId('id_postulacion')->constrained('postulaciones', 'id_postulacion')->cascadeOnDelete();
            $table->enum('estado_anterior', ['pendiente', 'en_formacion', 'apto', 'no_apto'])->nullable();
            $table->enum('estado_nuevo', ['pendiente', 'en_formacion', 'apto', 'no_apto']);
            $table->foreignId('id_admin')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('comentario', 500)->nullable();
            $table->timestamp('fecha')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados');
        Schema::dropIfExists('postulaciones');
        Schema::dropIfExists('procesos_seleccion');
    }
};
