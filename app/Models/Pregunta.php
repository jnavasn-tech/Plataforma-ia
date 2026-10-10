<?php

namespace App\Models;

use App\Enums\UsoPregunta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pregunta del banco (diagnóstico o evaluación de módulo). */
class Pregunta extends Model
{
    use HasFactory;

    protected $table = 'preguntas';

    protected $primaryKey = 'id_pregunta';

    protected $fillable = ['id_area', 'id_modulo', 'uso', 'dificultad', 'enunciado', 'explicacion', 'activo', 'version', 'creado_por'];

    protected $casts = ['id_area' => 'integer', 'id_modulo' => 'integer', 'version' => 'integer', 'uso' => UsoPregunta::class, 'dificultad' => 'integer', 'activo' => 'boolean'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(AreaCompetencia::class, 'id_area');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ModuloContenido::class, 'id_modulo');
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(OpcionRespuesta::class, 'id_pregunta')->orderBy('orden');
    }
}
