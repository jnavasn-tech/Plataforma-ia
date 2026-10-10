<?php

namespace App\Models;

use App\Enums\EstadoPostulacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historial de cambios de estado del aspirante (trazabilidad del proceso, CU-05). */
class HistorialEstado extends Model
{
    public const CREATED_AT = 'fecha';

    public const UPDATED_AT = null;

    protected $table = 'historial_estados';

    protected $primaryKey = 'id_historial';

    protected $fillable = ['id_postulacion', 'estado_anterior', 'estado_nuevo', 'id_admin', 'comentario'];

    protected $casts = [
        'estado_anterior' => EstadoPostulacion::class,
        'estado_nuevo' => EstadoPostulacion::class,
        'fecha' => 'datetime',
    ];

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class, 'id_postulacion');
    }

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_admin');
    }
}
