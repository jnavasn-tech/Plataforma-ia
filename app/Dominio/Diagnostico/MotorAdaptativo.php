<?php

namespace App\Dominio\Diagnostico;

/**
 * Motor de selección adaptativa de ítems (RF-02, CU-02).
 *
 * Regla: se aplican N ítems por área (6 por defecto, 30 en total para 5 áreas).
 * El primer ítem de cada área tiene dificultad 2 (media). Si la respuesta anterior
 * del área fue correcta, la dificultad objetivo sube un nivel; si fue incorrecta,
 * baja un nivel, siempre dentro del rango 1..3. Cuando no existe un ítem libre con
 * la dificultad objetivo, se elige el de dificultad más cercana.
 *
 * La adaptación es local y determinística respecto de las reglas; el azar solo se
 * usa para elegir entre ítems equivalentes, por lo que puede inyectarse en pruebas.
 */
final class MotorAdaptativo
{
    public const DIFICULTAD_MIN = 1;
    public const DIFICULTAD_MAX = 3;
    public const DIFICULTAD_INICIAL = 2;

    /** @var callable(int,int):int */
    private $aleatorio;

    /** @param (callable(int,int):int)|null $aleatorio función que devuelve un entero entre min y max */
    public function __construct(private readonly int $itemsPorArea = 6, ?callable $aleatorio = null)
    {
        if ($itemsPorArea < 1) {
            throw new \InvalidArgumentException('La cantidad de ítems por área debe ser mayor que cero.');
        }
        $this->aleatorio = $aleatorio ?? static fn (int $min, int $max): int => random_int($min, $max);
    }

    public function itemsPorArea(): int
    {
        return $this->itemsPorArea;
    }

    /** @param string[] $ordenAreas */
    public function totalItems(array $ordenAreas): int
    {
        return count($ordenAreas) * $this->itemsPorArea;
    }

    /**
     * Devuelve las áreas que no cuentan con ítems suficientes.
     *
     * @param  string[]  $ordenAreas
     * @param  ItemBanco[]  $banco
     * @return string[]
     */
    public function areasInsuficientes(array $ordenAreas, array $banco): array
    {
        $conteo = array_fill_keys($ordenAreas, 0);
        foreach ($banco as $item) {
            if (array_key_exists($item->codigoArea, $conteo)) {
                $conteo[$item->codigoArea]++;
            }
        }

        return array_keys(array_filter($conteo, fn (int $n) => $n < $this->itemsPorArea));
    }

    /**
     * Calcula la dificultad objetivo para el siguiente ítem de un área.
     *
     * @param  RespuestaRegistrada[]  $historialArea respuestas del área en orden de registro
     */
    public function dificultadObjetivo(array $historialArea): int
    {
        if ($historialArea === []) {
            return self::DIFICULTAD_INICIAL;
        }
        $ultima = $historialArea[array_key_last($historialArea)];
        $objetivo = $ultima->dificultad + ($ultima->correcta ? 1 : -1);

        return max(self::DIFICULTAD_MIN, min(self::DIFICULTAD_MAX, $objetivo));
    }

    /**
     * Selecciona el siguiente ítem o devuelve null si el diagnóstico está completo.
     *
     * @param  string[]  $ordenAreas códigos de área en el orden de aplicación
     * @param  ItemBanco[]  $banco ítems activos de uso diagnóstico
     * @param  RespuestaRegistrada[]  $historial respuestas registradas
     * @param  int[]  $idsExcluidos ítems ya presentados (incluye el pendiente, si existe)
     *
     * @throws BancoInsuficienteException
     */
    public function siguienteItem(array $ordenAreas, array $banco, array $historial, array $idsExcluidos = []): ?ItemBanco
    {
        $usados = array_flip(array_merge($idsExcluidos, array_map(fn (RespuestaRegistrada $r) => $r->idPregunta, $historial)));

        foreach ($ordenAreas as $area) {
            $historialArea = array_values(array_filter($historial, fn (RespuestaRegistrada $r) => $r->codigoArea === $area));
            if (count($historialArea) >= $this->itemsPorArea) {
                continue;
            }

            $candidatos = array_values(array_filter(
                $banco,
                fn (ItemBanco $i) => $i->codigoArea === $area && ! isset($usados[$i->idPregunta])
            ));
            if ($candidatos === []) {
                throw new BancoInsuficienteException([$area]);
            }

            return $this->elegirPorDificultad($candidatos, $this->dificultadObjetivo($historialArea));
        }

        return null;
    }

    /** @param ItemBanco[] $candidatos */
    private function elegirPorDificultad(array $candidatos, int $objetivo): ItemBanco
    {
        $distanciaMinima = min(array_map(fn (ItemBanco $i) => abs($i->dificultad - $objetivo), $candidatos));
        $mejores = array_values(array_filter($candidatos, fn (ItemBanco $i) => abs($i->dificultad - $objetivo) === $distanciaMinima));
        $indice = ($this->aleatorio)(0, count($mejores) - 1);

        return $mejores[$indice];
    }
}
