<?php

namespace App\Models;

use App\Enums\NivelCompetencia;
use App\Enums\OrigenGeneracion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Perfil de competencias (tabla original perfiles_competencia). RF-03.
 * areas_evaluadas_json: [{codigo, nombre, puntaje, nivel, respondidas}]
 */
class PerfilCompetencia extends Model
{
    use HasFactory;

    protected $table = 'perfiles_competencia';

    protected $primaryKey = 'id_perfil';

    protected $fillable = [
        'id_diagnostico', 'nivel_general', 'puntaje_global', 'areas_evaluadas_json', 'resumen', 'fortalezas_json',
        'areas_mejora_json', 'recomendacion', 'origen_interpretacion', 'version_prompt',
    ];

    protected $casts = [
        'id_diagnostico' => 'integer',
        'nivel_general' => NivelCompetencia::class,
        'nivel_control' => NivelCompetencia::class,
        'origen_interpretacion' => OrigenGeneracion::class,
        'puntaje_global' => 'float',
        'areas_evaluadas_json' => 'array',
        'fortalezas_json' => 'array',
        'areas_mejora_json' => 'array',
        'control_registrado_en' => 'datetime',
    ];

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class, 'id_diagnostico');
    }

    public function ruta(): HasOne
    {
        return $this->hasOne(RutaAprendizaje::class, 'id_perfil');
    }

    /** @return array<string,float> código de área => puntaje */
    public function puntajesPorCodigo(): array
    {
        $resultado = [];
        foreach ($this->areas_evaluadas_json ?? [] as $area) {
            $resultado[$area['codigo']] = (float) $area['puntaje'];
        }

        return $resultado;
    }

    /** @return array<string,float> nombre de área => puntaje (para gráficos) */
    public function puntajesPorNombre(): array
    {
        $resultado = [];
        foreach ($this->areas_evaluadas_json ?? [] as $area) {
            $resultado[$area['nombre']] = (float) $area['puntaje'];
        }

        return $resultado;
    }
}
