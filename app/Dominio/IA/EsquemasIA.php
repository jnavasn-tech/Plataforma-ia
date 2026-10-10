<?php

namespace App\Dominio\IA;

use InvalidArgumentException;

/**
 * Esquemas JSON de salida para las tres solicitudes a la IA.
 *
 * - esquema($clave): versión compatible con "Structured Outputs" (strict) que se envía al proveedor.
 * - esquema($clave, true): misma estructura con límites adicionales (longitudes y cantidades)
 *   que la plataforma verifica localmente antes de aceptar la respuesta.
 */
final class EsquemasIA
{
    public const PERFIL = 'diagnostico';
    public const RUTA = 'ruta';
    public const RETROALIMENTACION = 'retroalimentacion';

    /** @return array<string,mixed> */
    public static function esquema(string $clave, bool $conLimites = false): array
    {
        $texto = fn (int $max) => $conLimites ? ['type' => 'string', 'minLength' => 1, 'maxLength' => $max] : ['type' => 'string'];
        $lista = fn (int $maxItems, int $maxTexto) => array_merge(
            ['type' => 'array', 'items' => $texto($maxTexto)],
            $conLimites ? ['maxItems' => $maxItems] : []
        );

        return match ($clave) {
            self::PERFIL => self::objeto([
                'resumen' => $texto(1200),
                'fortalezas' => $lista(6, 300),
                'areas_mejora' => $lista(6, 300),
                'recomendacion_general' => $texto(600),
            ]),
            self::RUTA => self::objeto([
                'modulos' => array_merge([
                    'type' => 'array',
                    'items' => self::objeto([
                        'id_modulo' => $conLimites ? ['type' => 'integer', 'minimum' => 1] : ['type' => 'integer'],
                        'prioridad' => ['type' => 'string', 'enum' => ['alta', 'media', 'baja']],
                        'justificacion' => $texto(400),
                    ]),
                ], $conLimites ? ['minItems' => 1, 'maxItems' => 15] : []),
                'narrativa' => $texto(1500),
                'fortalezas' => $lista(6, 300),
                'areas_mejora' => $lista(6, 300),
            ]),
            self::RETROALIMENTACION => self::objeto([
                'mensaje' => $texto(1000),
                'sugerencias' => $lista(5, 300),
                'siguiente_paso' => $texto(300),
            ]),
            default => throw new InvalidArgumentException("Esquema desconocido: {$clave}"),
        };
    }

    /**
     * Formato response_format de la API de Chat Completions.
     *
     * @return array<string,mixed>
     */
    public static function formatoRespuesta(string $clave): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'respuesta_'.$clave,
                'strict' => true,
                'schema' => self::esquema($clave),
            ],
        ];
    }

    /** @param array<string,array<string,mixed>> $propiedades */
    private static function objeto(array $propiedades): array
    {
        return [
            'type' => 'object',
            'properties' => $propiedades,
            'required' => array_keys($propiedades),
            'additionalProperties' => false,
        ];
    }
}
