<?php

namespace App\Models;

use App\Enums\EstadoRutaModulo;
use App\Enums\Prioridad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Módulo asignado a una ruta (tabla adicional ruta_modulos). CU-04. */
class RutaModulo extends Model
{
    protected $table = 'ruta_modulos';

    protected $primaryKey = 'id_ruta_modulo';

    protected $fillable = ['id_ruta', 'id_modulo', 'orden', 'prioridad', 'justificacion', 'estado', 'porcentaje_avance', 'completado_en'];

    protected $casts = [
        'id_ruta' => 'integer',
        'id_modulo' => 'integer',
        'orden' => 'integer',
        'prioridad' => Prioridad::class,
        'estado' => EstadoRutaModulo::class,
        'porcentaje_avance' => 'float',
        'completado_en' => 'datetime',
    ];

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(RutaAprendizaje::class, 'id_ruta');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ModuloContenido::class, 'id_modulo');
    }

    public function progresos(): HasMany
    {
        return $this->hasMany(ProgresoRecurso::class, 'id_ruta_modulo');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'id_ruta_modulo')->orderBy('intento');
    }

    public function valoracion(): HasOne
    {
        return $this->hasOne(ValoracionModulo::class, 'id_ruta_modulo');
    }
}
