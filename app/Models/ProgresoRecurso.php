<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Avance del aspirante en cada recurso (tabla progreso_recursos citada en CU-04). */
class ProgresoRecurso extends Model
{
    protected $table = 'progreso_recursos';

    protected $primaryKey = 'id_progreso';

    protected $fillable = ['id_usuario', 'id_recurso', 'id_ruta_modulo', 'iniciado_en', 'completado_en', 'reportado_no_disponible_en'];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_recurso' => 'integer',
        'id_ruta_modulo' => 'integer',
        'iniciado_en' => 'datetime',
        'completado_en' => 'datetime',
        'reportado_no_disponible_en' => 'datetime',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(RecursoAprendizaje::class, 'id_recurso');
    }

    public function rutaModulo(): BelongsTo
    {
        return $this->belongsTo(RutaModulo::class, 'id_ruta_modulo');
    }
}
