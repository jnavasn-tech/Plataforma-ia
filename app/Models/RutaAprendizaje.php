<?php

namespace App\Models;

use App\Enums\EstadoRuta;
use App\Enums\EstadoRutaModulo;
use App\Enums\OrigenGeneracion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Ruta de aprendizaje (tabla original rutas_aprendizaje). RF-04, CU-03. */
class RutaAprendizaje extends Model
{
    use HasFactory;

    public const CREATED_AT = 'fecha_generacion';

    protected $table = 'rutas_aprendizaje';

    protected $primaryKey = 'id_ruta';

    protected $fillable = [
        'id_perfil', 'estado', 'origen', 'narrativa', 'fortalezas_json', 'areas_mejora_json', 'es_ruta_avanzada',
        'modelo_ia', 'version_prompt', 'tiempo_generacion_ms', 'completada_en',
    ];

    protected $casts = [
        'estado' => EstadoRuta::class,
        'origen' => OrigenGeneracion::class,
        'fortalezas_json' => 'array',
        'areas_mejora_json' => 'array',
        'es_ruta_avanzada' => 'boolean',
        'fecha_generacion' => 'datetime',
        'completada_en' => 'datetime',
    ];

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(PerfilCompetencia::class, 'id_perfil');
    }

    public function modulos(): HasMany
    {
        return $this->hasMany(RutaModulo::class, 'id_ruta')->orderBy('orden');
    }

    public function propietarioId(): int
    {
        return (int) $this->perfil->diagnostico->id_usuario;
    }

    public function porcentajeAvance(): float
    {
        $modulos = $this->modulos;
        if ($modulos->isEmpty()) {
            return 0.0;
        }

        return round($modulos->filter(fn (RutaModulo $m) => $m->estado === EstadoRutaModulo::Completado)->count() / $modulos->count() * 100, 2);
    }
}
