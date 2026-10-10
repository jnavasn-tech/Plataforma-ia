<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tabla adicional citada en CU-07: catálogo de recursos (videos, artículos, ejercicios, lecturas internas). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recursos_aprendizaje', function (Blueprint $table) {
            $table->id('id_recurso');
            $table->foreignId('id_modulo')->constrained('modulos_contenido', 'id_modulo')->restrictOnDelete();
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['lectura', 'video', 'articulo', 'ejercicio']);
            $table->string('url', 500)->nullable()->unique();
            $table->text('contenido')->nullable();
            $table->unsignedSmallInteger('duracion_minutos')->nullable();
            $table->enum('estado', ['activo', 'pendiente_verificacion', 'inactivo'])->default('activo');
            $table->foreignId('creado_por')->nullable()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->timestamps();
            $table->index(['id_modulo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos_aprendizaje');
    }
};
