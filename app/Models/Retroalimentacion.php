<?php

namespace App\Models;

use App\Enums\OrigenGeneracion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Retroalimentación sobre una evaluación (tabla original retroalimentaciones). RF-07. */
class Retroalimentacion extends Model
{
    public const CREATED_AT = 'fecha';

    protected $table = 'retroalimentaciones';

    protected $primaryKey = 'id_retro';

    protected $fillable = ['id_evaluacion', 'texto_generado', 'sugerencias_json', 'siguiente_paso', 'origen', 'version_prompt'];

    protected $casts = ['origen' => OrigenGeneracion::class, 'sugerencias_json' => 'array', 'fecha' => 'datetime'];

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class, 'id_evaluacion');
    }
}
