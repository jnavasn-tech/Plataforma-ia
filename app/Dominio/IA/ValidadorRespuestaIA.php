<?php

namespace App\Dominio\IA;

use JsonException;

/**
 * Valida las respuestas del modelo antes de usarlas (estructura + reglas de negocio + contenido).
 * Cualquier incumplimiento lanza RespuestaIAInvalidaException y el servicio aplica el fallback.
 */
final class ValidadorRespuestaIA
{
    /**
     * Expresiones que no deben aparecer en una respuesta generada:
     *  - decisiones de contratación (la decisión es exclusivamente humana, CU-05);
     *  - referencias a atributos personales protegidos (no discriminación).
     */
    private const PATRONES_NO_PERMITIDOS = [
        '/\b(contrataci[oó]n|contratar\w*|contratad[oa]s?|no\s+apto|apto\s+para\s+el\s+puesto|seleccionad[oa]\s+para\s+el\s+puesto|descartad[oa]\s+del\s+proceso)\b/iu',
        '/\b(religi\w*|etni\w*|raza|g[eé]nero|embaraz\w*|discapacidad|orientaci[oó]n\s+sexual|estado\s+civil)\b/iu',
    ];

    public function __construct(private readonly ValidadorEsquema $validadorEsquema = new ValidadorEsquema())
    {
    }

    /** @return array<string,mixed> */
    public function decodificar(string $contenido): array
    {
        try {
            $datos = json_decode(trim($contenido), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RespuestaIAInvalidaException('json_invalido', 'La respuesta del modelo no es JSON válido.');
        }
        if (! is_array($datos) || array_is_list($datos)) {
            throw new RespuestaIAInvalidaException('json_invalido', 'La respuesta del modelo debe ser un objeto JSON.');
        }

        return $datos;
    }

    /** @return array{resumen:string,fortalezas:string[],areas_mejora:string[],recomendacion_general:string} */
    public function validarPerfil(array $datos): array
    {
        $this->validarEstructura($datos, EsquemasIA::PERFIL);
        $this->validarContenido($datos);

        return $datos;
    }

    /**
     * @param  int[]  $idsCatalogo módulos ofrecidos en la solicitud
     * @param  array<int,string>  $areaPorModulo id_modulo => código de área
     * @param  string[]  $areasObligatorias áreas en nivel básico que la ruta debe cubrir
     * @return array{modulos:array<int,array{id_modulo:int,prioridad:string,justificacion:string}>,narrativa:string,fortalezas:string[],areas_mejora:string[]}
     */
    public function validarRuta(array $datos, array $idsCatalogo, array $areaPorModulo, array $areasObligatorias, int $maxModulos): array
    {
        $this->validarEstructura($datos, EsquemasIA::RUTA);
        $this->validarContenido($datos);

        $ids = array_map(fn (array $m) => $m['id_modulo'], $datos['modulos']);
        if (count($ids) > $maxModulos) {
            throw new RespuestaIAInvalidaException('esquema_invalido', "La ruta excede el máximo de {$maxModulos} módulos.");
        }
        if (count($ids) !== count(array_unique($ids))) {
            throw new RespuestaIAInvalidaException('modulo_duplicado', 'La ruta contiene módulos repetidos.');
        }
        $desconocidos = array_diff($ids, $idsCatalogo);
        if ($desconocidos !== []) {
            throw new RespuestaIAInvalidaException('modulo_desconocido', 'La ruta contiene módulos fuera del catálogo enviado.');
        }
        $areasCubiertas = array_unique(array_map(fn (int $id) => $areaPorModulo[$id] ?? '', $ids));
        $faltantes = array_diff($areasObligatorias, $areasCubiertas);
        if ($faltantes !== []) {
            throw new RespuestaIAInvalidaException('cobertura_insuficiente', 'La ruta no cubre las áreas con nivel básico: '.implode(', ', $faltantes));
        }

        return $datos;
    }

    /** @return array{mensaje:string,sugerencias:string[],siguiente_paso:string} */
    public function validarRetroalimentacion(array $datos): array
    {
        $this->validarEstructura($datos, EsquemasIA::RETROALIMENTACION);
        $this->validarContenido($datos);

        return $datos;
    }

    private function validarEstructura(array $datos, string $clave): void
    {
        $errores = $this->validadorEsquema->validar($datos, EsquemasIA::esquema($clave, true));
        if ($errores !== []) {
            throw new RespuestaIAInvalidaException('esquema_invalido', 'Respuesta fuera de esquema: '.implode('; ', array_slice($errores, 0, 3)));
        }
    }

    private function validarContenido(array $datos): void
    {
        array_walk_recursive($datos, function ($valor) {
            if (! is_string($valor)) {
                return;
            }
            foreach (self::PATRONES_NO_PERMITIDOS as $patron) {
                if (preg_match($patron, $valor) === 1) {
                    throw new RespuestaIAInvalidaException('contenido_no_permitido', 'La respuesta contiene contenido fuera del alcance permitido.');
                }
            }
        });
    }
}
