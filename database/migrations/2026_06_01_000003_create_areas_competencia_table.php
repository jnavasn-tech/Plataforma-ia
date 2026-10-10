<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tabla adicional: catálogo de áreas de competencia con su referencia a DigComp 2.2. RF-02, RF-03, CU-07. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas_competencia', function (Blueprint $table) {
            $table->id('id_area');
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 80);
            $table->text('descripcion')->nullable();
            $table->string('referencia_digcomp', 150)->nullable();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas_competencia');
    }
};
