<?php

namespace App\Models;

use App\Enums\Rol;
use App\Notifications\RestablecerContrasenaNotificacion;
use App\Notifications\VerificarCorreoNotificacion;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Entidad Usuario (tabla usuarios). Aspirantes y administradores RRHH. RF-01, CU-01, CU-08.
 *
 * @property int $id_usuario
 * @property string $nombre
 * @property string $correo
 * @property Rol $rol
 */
class Usuario extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const CREATED_AT = 'fecha_registro';

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    /** Solo campos editables por formularios; rol y estados se asignan explícitamente en servicios. */
    protected $fillable = ['nombre', 'correo', 'telefono', 'cargo'];

    protected $hidden = ['contrasena_hash', 'remember_token'];

    protected $casts = [
        'rol' => Rol::class,
        'email_verified_at' => 'datetime',
        'consentimiento_privacidad_at' => 'datetime',
        'consentimiento_tutor_at' => 'datetime',
        'ultimo_acceso_at' => 'datetime',
        'es_menor_edad' => 'boolean',
        'activo' => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return $this->contrasena_hash;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function getEmailForVerification(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerContrasenaNotificacion($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerificarCorreoNotificacion());
    }

    public function esAdministrador(): bool
    {
        return $this->rol === Rol::AdminRrhh;
    }

    public function esAspirante(): bool
    {
        return $this->rol === Rol::Aspirante;
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class, 'id_usuario');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'id_usuario');
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'id_usuario');
    }

    /** Postulación más reciente (proceso de selección vigente). */
    public function postulacionActual(): ?Postulacion
    {
        return $this->postulaciones()->latest('created_at')->latest('id_postulacion')->first();
    }

    /** Último diagnóstico completado del tipo indicado. */
    public function ultimoDiagnosticoCompletado(string $tipo = 'pretest'): ?Diagnostico
    {
        return $this->diagnosticos()
            ->where('tipo', $tipo)
            ->where('estado', 'completado')
            ->latest('finalizado_en')
            ->first();
    }
}
