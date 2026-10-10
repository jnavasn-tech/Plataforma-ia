<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Proceso de selección de Grupo Ilimitado, S. A. (precondición de CU-05 y filtro de CU-06). */
class ProcesoSeleccion extends Model
{
    use HasFactory;

    protected $table = 'procesos_seleccion';

    protected $primaryKey = 'id_proceso';

    protected $fillable = ['nombre', 'puesto', 'fecha_inicio', 'fecha_fin', 'activo'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'activo' => 'boolean'];

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'id_proceso');
    }

    public static function vigente(): ?self
    {
        return static::where('activo', true)->orderByDesc('fecha_inicio')->first();
    }
}
