<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla original "modulos_contenido". Cambio frente al Capítulo IV: deja de depender de id_ruta y pasa a ser
 * un catálogo reutilizable por área y nivel; la asignación a cada ruta se registra en "ruta_modulos".
 * RF-05, CU-04, CU-07.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos_contenido', function (Blueprint $table) {
            $table->id('id_modulo');
            $table->foreignId('id_area')->constrained('areas_competencia', 'id_area')->restrictOnDelete();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado']);
            $table->unsignedSmallInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['id_area', 'nivel', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos_contenido');
    }
};
