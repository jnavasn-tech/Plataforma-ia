<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla original del modelo (Capítulo IV): usuarios.
 * Se conservan id_usuario, nombre, correo, contrasena_hash (sin "ñ" en el identificador), rol y fecha_registro;
 * se añaden campos de verificación, consentimiento y seguridad. RF-01, CU-01, CU-08.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre', 120);
            $table->string('correo', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('contrasena_hash', 255);
            $table->enum('rol', ['aspirante', 'admin_rrhh'])->default('aspirante')->index();
            $table->string('telefono', 20)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->boolean('es_menor_edad')->default(false);
            $table->timestamp('consentimiento_privacidad_at')->nullable();
            $table->string('version_aviso_privacidad', 10)->nullable();
            $table->timestamp('consentimiento_tutor_at')->nullable();
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamp('fecha_registro')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
