<?php

namespace App\Models;

use App\Enums\NivelCompetencia;
use App\Enums\EstadoRecurso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Módulo formativo del catálogo (tabla original modulos_contenido). RF-05, CU-04, CU-07. */
class ModuloContenido extends Model
{
    use HasFactory;

    protected $table = 'modulos_contenido';

    protected $primaryKey = 'id_modulo';

    protected $fillable = ['id_area', 'codigo', 'nombre', 'descripcion', 'nivel', 'orden', 'activo'];

    protected $casts = ['id_area' => 'integer', 'nivel' => NivelCompetencia::class, 'activo' => 'boolean', 'orden' => 'integer'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(AreaCompetencia::class, 'id_area');
    }

    public function recursos(): HasMany
    {
        return $this->hasMany(RecursoAprendizaje::class, 'id_modulo');
    }

    public function recursosActivos(): HasMany
    {
        return $this->recursos()->where('estado', EstadoRecurso::Activo->value)->orderBy('id_recurso');
    }

    public function preguntasEvaluacion(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'id_modulo')
            ->where('uso', 'evaluacion_modulo')
            ->where('activo', true)
            ->orderBy('id_pregunta');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
