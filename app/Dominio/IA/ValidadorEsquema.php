<?php

namespace App\Dominio\IA;

/**
 * Validador mínimo de JSON Schema (subconjunto: object, array, string, integer, number, boolean,
 * enum, required, additionalProperties=false, minItems, maxItems, minLength, maxLength, minimum, maximum).
 * Se implementa localmente para no depender de librerías adicionales.
 */
final class ValidadorEsquema
{
    /**
     * @param  array<string,mixed>  $esquema
     * @return string[] lista de errores (vacía si es válido)
     */
    public function validar(mixed $valor, array $esquema, string $ruta = '$'): array
    {
        $errores = [];
        $tipo = $esquema['type'] ?? null;

        if (! $this->tipoValido($valor, $tipo)) {
            return ["{$ruta}: se esperaba {$tipo}"];
        }

        if (isset($esquema['enum']) && ! in_array($valor, $esquema['enum'], true)) {
            $errores[] = "{$ruta}: valor fuera del dominio permitido";
        }

        if ($tipo === 'string') {
            $longitud = mb_strlen($valor);
            if (isset($esquema['minLength']) && $longitud < $esquema['minLength']) {
                $errores[] = "{$ruta}: texto demasiado corto";
            }
            if (isset($esquema['maxLength']) && $longitud > $esquema['maxLength']) {
                $errores[] = "{$ruta}: texto excede {$esquema['maxLength']} caracteres";
            }
        }

        if (in_array($tipo, ['integer', 'number'], true)) {
            if (isset($esquema['minimum']) && $valor < $esquema['minimum']) {
                $errores[] = "{$ruta}: valor menor que el mínimo";
            }
            if (isset($esquema['maximum']) && $valor > $esquema['maximum']) {
                $errores[] = "{$ruta}: valor mayor que el máximo";
            }
        }

        if ($tipo === 'array') {
            $cantidad = count($valor);
            if (isset($esquema['minItems']) && $cantidad < $esquema['minItems']) {
                $errores[] = "{$ruta}: se requieren al menos {$esquema['minItems']} elementos";
            }
            if (isset($esquema['maxItems']) && $cantidad > $esquema['maxItems']) {
                $errores[] = "{$ruta}: se permiten como máximo {$esquema['maxItems']} elementos";
            }
            foreach ($valor as $i => $elemento) {
                $errores = array_merge($errores, $this->validar($elemento, $esquema['items'] ?? [], "{$ruta}[{$i}]"));
            }
        }

        if ($tipo === 'object') {
            foreach ($esquema['required'] ?? [] as $requerida) {
                if (! array_key_exists($requerida, $valor)) {
                    $errores[] = "{$ruta}.{$requerida}: propiedad requerida ausente";
                }
            }
            $propiedades = $esquema['properties'] ?? [];
            if (($esquema['additionalProperties'] ?? true) === false) {
                foreach (array_diff(array_keys($valor), array_keys($propiedades)) as $extra) {
                    $errores[] = "{$ruta}.{$extra}: propiedad no permitida";
                }
            }
            foreach ($propiedades as $nombre => $subesquema) {
                if (array_key_exists($nombre, $valor)) {
                    $errores = array_merge($errores, $this->validar($valor[$nombre], $subesquema, "{$ruta}.{$nombre}"));
                }
            }
        }

        return $errores;
    }

    private function tipoValido(mixed $valor, ?string $tipo): bool
    {
        return match ($tipo) {
            'object' => is_array($valor) && ($valor === [] || ! array_is_list($valor)),
            'array' => is_array($valor) && array_is_list($valor),
            'string' => is_string($valor),
            'integer' => is_int($valor),
            'number' => is_int($valor) || is_float($valor),
            'boolean' => is_bool($valor),
            null => true,
            default => false,
        };
    }
}
