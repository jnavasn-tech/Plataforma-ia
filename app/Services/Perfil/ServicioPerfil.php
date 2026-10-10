<?php

namespace App\Services\Perfil;

use App\Dominio\IA\MinimizadorDatos;
use App\Dominio\IA\ValidadorRespuestaIA;
use App\Dominio\Perfil\InterpretacionPerfilReglas;
use App\Enums\ClavePrompt;
use App\Enums\NivelCompetencia;
use App\Models\PerfilCompetencia;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use App\Services\IA\ServicioIA;

/** Interpretación del perfil (prompt de diagnóstico) y registro del nivel de control (indicador de precisión). */
class ServicioPerfil
{
    public function __construct(
        private readonly ServicioIA $ia,
        private readonly ValidadorRespuestaIA $validador,
        private readonly MinimizadorDatos $minimizador,
        private readonly InterpretacionPerfilReglas $reglas,
        private readonly RegistradorBitacora $bitacora,
    ) {
    }

    public function interpretar(PerfilCompetencia $perfil): PerfilCompetencia
    {
        if ($perfil->origen_interpretacion !== null) {
            return $perfil;
        }

        $areas = $perfil->areas_evaluadas_json;
        $puntajes = $perfil->puntajesPorCodigo();
        $nombres = array_column($areas, 'nombre', 'codigo');

        $entrada = $this->minimizador->entradaPerfil($areas, $perfil->nivel_general->value, $perfil->puntaje_global);

        $resultado = $this->ia->generar(
            ClavePrompt::Diagnostico,
            $entrada,
            fn (array $datos) => $this->validador->validarPerfil($datos),
            fn () => $this->reglas->generar($puntajes, $nombres, $perfil->nivel_general, $perfil->puntaje_global),
            'perfil',
            $perfil->id_perfil,
        );

        $perfil->forceFill([
            'resumen' => $resultado->datos['resumen'],
            'fortalezas_json' => $resultado->datos['fortalezas'],
            'areas_mejora_json' => $resultado->datos['areas_mejora'],
            'recomendacion' => $resultado->datos['recomendacion_general'],
            'origen_interpretacion' => $resultado->origen,
            'version_prompt' => $resultado->versionPrompt,
        ])->save();

        return $perfil;
    }

    /** Registra el nivel obtenido en la evaluación de control (objetivo específico b). */
    public function registrarNivelControl(PerfilCompetencia $perfil, NivelCompetencia $nivel, Usuario $administrador): void
    {
        $perfil->forceFill([
            'nivel_control' => $nivel,
            'control_registrado_por' => $administrador->id_usuario,
            'control_registrado_en' => now(),
        ])->save();

        $this->bitacora->registrar('nivel_control_registrado', $administrador, $perfil, ['nivel_control' => $nivel->value]);
    }
}
