<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bitácora de actividad (sin datos sensibles). CU-01, CU-06, RF-08. */
class Bitacora extends Model
{
    public const CREATED_AT = 'fecha';

    public const UPDATED_AT = null;

    protected $table = 'bitacora';

    protected $primaryKey = 'id_bitacora';

    protected $fillable = ['id_usuario', 'accion', 'entidad', 'id_entidad', 'detalle_json', 'ip_anonimizada'];

    protected $casts = ['detalle_json' => 'array', 'fecha' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
