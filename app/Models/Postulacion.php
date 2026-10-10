<?php

namespace App\Models;

use App\Enums\EstadoPostulacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Participación de un aspirante en un proceso de selección; su estado lo decide RRHH (CU-05). */
class Postulacion extends Model
{
    use HasFactory;

    protected $table = 'postulaciones';

    protected $primaryKey = 'id_postulacion';

    protected $fillable = ['id_usuario', 'id_proceso', 'estado', 'actualizado_por'];

    protected $casts = ['id_usuario' => 'integer', 'id_proceso' => 'integer', 'estado' => EstadoPostulacion::class];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(ProcesoSeleccion::class, 'id_proceso');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class, 'id_postulacion')->orderByDesc('fecha');
    }
}
