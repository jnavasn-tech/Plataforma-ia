<?php

namespace App\Models;

use App\Enums\EstadoSolicitudIA;
use Illuminate\Database\Eloquent\Model;

/** Registro técnico de cada solicitud al proveedor de IA (no almacena prompts ni respuestas). */
class BitacoraIA extends Model
{
    public const CREATED_AT = 'fecha';

    public const UPDATED_AT = null;

    protected $table = 'bitacora_ia';

    protected $primaryKey = 'id_bitacora_ia';

    protected $fillable = [
        'tipo', 'estado', 'modelo', 'version_prompt', 'latencia_ms', 'intentos', 'tokens_entrada',
        'tokens_salida', 'codigo_error', 'referencia_tipo', 'referencia_id',
    ];

    protected $casts = ['estado' => EstadoSolicitudIA::class, 'fecha' => 'datetime'];
}
