<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catálogo de áreas de competencia (Ofimática, Redes, Ciberseguridad, Colaboración digital, Productividad). */
class AreaCompetencia extends Model
{
    use HasFactory;

    protected $table = 'areas_competencia';

    protected $primaryKey = 'id_area';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'referencia_digcomp', 'orden', 'activo'];

    protected $casts = ['activo' => 'boolean', 'orden' => 'integer'];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function modulos(): HasMany
    {
        return $this->hasMany(ModuloContenido::class, 'id_area');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'id_area');
    }
}
