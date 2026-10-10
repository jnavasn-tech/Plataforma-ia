<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Reactivo presentado y respondido dentro de un diagnóstico. */
class RespuestaDiagnostico extends Model
{
    protected $table = 'respuestas_diagnostico';

    protected $primaryKey = 'id_respuesta';

    protected $fillable = ['id_diagnostico', 'id_pregunta', 'id_opcion', 'orden', 'dificultad', 'es_correcta', 'presentada_en', 'respondida_en', 'tiempo_respuesta_ms', 'tiempo_servidor_ms'];

    protected $casts = [
        'id_diagnostico' => 'integer',
        'id_pregunta' => 'integer',
        'orden' => 'integer',
        'es_correcta' => 'boolean',
        'presentada_en' => 'datetime',
        'respondida_en' => 'datetime',
        'dificultad' => 'integer',
    ];

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class, 'id_diagnostico');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'id_pregunta');
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(OpcionRespuesta::class, 'id_opcion');
    }
}
