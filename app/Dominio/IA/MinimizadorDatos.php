<?php

namespace App\Dominio\IA;

/**
 * Construye las entradas que se envían al modelo aplicando minimización de datos:
 * solo puntajes, niveles, nombres de áreas/módulos y parámetros pedagógicos.
 * Nunca incluye nombre, correo, teléfono, contraseña ni identificadores del usuario.
 */
final class MinimizadorDatos
{
    private const CLAVES_PROHIBIDAS = [
        'nombre_completo', 'nombre_usuario', 'correo', 'email', 'telefono', 'contrasena', 'password',
        'id_usuario', 'usuario', 'ip', 'token', 'direccion', 'dpi', 'fecha_nacimiento',
    ];

    private const PATRON_CORREO = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

    private const PATRON_TELEFONO = '/(?<![\d.])(\+?502[\s-]?)?\d{4}[\s-]?\d{4}(?![\d.])/';

    /**
     * @param  array<int,array{codigo:string,nombre:string,puntaje:float,nivel:string}>  $areas
     * @return array<string,mixed>
     */
    public function entradaPerfil(array $areas, string $nivelGeneral, float $puntajeGlobal): array
    {
        $entrada = ['perfil' => $this->perfil($areas, $nivelGeneral, $puntajeGlobal)];
        $this->asegurarSinDatosPersonales($entrada);

        return $entrada;
    }

    /**
     * @param  array<int,array{codigo:string,nombre:string,puntaje:float,nivel:string}>  $areas
     * @param  array<int,array{id_modulo:int,area:string,nombre:string,nivel:string}>  $catalogo
     * @param  string[]  $areasObligatorias
     * @return array<string,mixed>
     */
    public function entradaRuta(array $areas, string $nivelGeneral, float $puntajeGlobal, array $catalogo, ?string $puestoObjetivo, int $maxModulos, array $areasObligatorias): array
    {
        $entrada = [
            'perfil' => $this->perfil($areas, $nivelGeneral, $puntajeGlobal),
            'puesto_objetivo' => $puestoObjetivo ? mb_substr($puestoObjetivo, 0, 120) : 'Puesto operativo de nivel de entrada',
            'catalogo_modulos' => array_map(fn (array $m) => [
                'id_modulo' => (int) $m['id_modulo'],
                'area' => (string) $m['area'],
                'nombre' => mb_substr((string) $m['nombre'], 0, 150),
                'nivel' => (string) $m['nivel'],
            ], $catalogo),
            'restricciones' => [
                'max_modulos' => $maxModulos,
                'areas_obligatorias' => array_values($areasObligatorias),
            ],
        ];
        $this->asegurarSinDatosPersonales($entrada);

        return $entrada;
    }

    /**
     * @param  string[]  $temasAReforzar enunciados de preguntas no acertadas (truncados)
     * @return array<string,mixed>
     */
    public function entradaRetroalimentacion(string $modulo, string $area, string $nivel, float $calificacion, bool $aprobado, int $intento, int $maxIntentos, array $temasAReforzar): array
    {
        $entrada = [
            'evaluacion' => [
                'modulo' => mb_substr($modulo, 0, 150),
                'area' => $area,
                'nivel' => $nivel,
                'calificacion' => round($calificacion, 2),
                'aprobado' => $aprobado,
                'intento' => $intento,
                'max_intentos' => $maxIntentos,
                'temas_a_reforzar' => array_values(array_map(fn (string $t) => mb_substr($t, 0, 150), array_slice($temasAReforzar, 0, 5))),
            ],
        ];
        $this->asegurarSinDatosPersonales($entrada);

        return $entrada;
    }

    /** @throws DatosPersonalesDetectadosException */
    public function asegurarSinDatosPersonales(array $carga): void
    {
        array_walk_recursive($carga, function ($valor, $clave) {
            if (is_string($clave) && in_array(mb_strtolower($clave), self::CLAVES_PROHIBIDAS, true)) {
                throw new DatosPersonalesDetectadosException("Clave no permitida en la carga útil: {$clave}");
            }
            if (is_string($valor) && (preg_match(self::PATRON_CORREO, $valor) === 1 || preg_match(self::PATRON_TELEFONO, $valor) === 1)) {
                throw new DatosPersonalesDetectadosException('La carga útil contiene un posible dato de contacto.');
            }
        });
        foreach (self::CLAVES_PROHIBIDAS as $prohibida) {
            if ($this->contieneClave($carga, $prohibida)) {
                throw new DatosPersonalesDetectadosException("Clave no permitida en la carga útil: {$prohibida}");
            }
        }
    }

    private function contieneClave(array $datos, string $clave): bool
    {
        foreach ($datos as $k => $v) {
            if (is_string($k) && mb_strtolower($k) === $clave) {
                return true;
            }
            if (is_array($v) && $this->contieneClave($v, $clave)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int,array{codigo:string,nombre:string,puntaje:float,nivel:string}> $areas */
    private function perfil(array $areas, string $nivelGeneral, float $puntajeGlobal): array
    {
        return [
            'nivel_general' => $nivelGeneral,
            'puntaje_global' => round($puntajeGlobal, 2),
            'areas' => array_map(fn (array $a) => [
                'codigo' => (string) $a['codigo'],
                'nombre_area' => (string) $a['nombre'],
                'puntaje' => round((float) $a['puntaje'], 2),
                'nivel' => (string) $a['nivel'],
            ], $areas),
        ];
    }
}
