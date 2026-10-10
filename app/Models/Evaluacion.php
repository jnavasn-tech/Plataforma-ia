<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Evaluación de módulo (tabla original evaluaciones). RF-06. */
class Evaluacion extends Model
{
    use HasFactory;

    public const CREATED_AT = 'fecha';

    protected $table = 'evaluaciones';

    protected $primaryKey = 'id_evaluacion';

    protected $fillable = ['id_modulo', 'id_usuario', 'id_ruta_modulo', 'intento', 'calificacion', 'aprobado', 'respuestas_json'];

    protected $casts = ['id_usuario' => 'integer', 'id_modulo' => 'integer', 'id_ruta_modulo' => 'integer', 'intento' => 'integer', 'calificacion' => 'float', 'aprobado' => 'boolean', 'respuestas_json' => 'array', 'fecha' => 'datetime'];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ModuloContenido::class, 'id_modulo');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function rutaModulo(): BelongsTo
    {
        return $this->belongsTo(RutaModulo::class, 'id_ruta_modulo');
    }

    public function retroalimentacion(): HasOne
    {
        return $this->hasOne(Retroalimentacion::class, 'id_evaluacion');
    }
}
