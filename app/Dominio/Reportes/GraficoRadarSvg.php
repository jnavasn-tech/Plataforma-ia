<?php

namespace App\Dominio\Reportes;

use InvalidArgumentException;

/**
 * Genera un gráfico de radar en SVG del lado del servidor (CU-02 resultados, CU-06 reportes).
 * No requiere JavaScript, funciona en la web y dentro del PDF, e incluye título y descripción accesibles.
 */
final class GraficoRadarSvg
{
    /**
     * @param  array<string,float>  $serie etiqueta => valor (0-100)
     * @param  array<string,float>|null  $comparacion serie opcional (por ejemplo, postest)
     * @param  bool  $dividirEtiquetas divide etiquetas largas en dos líneas (desactivar para DomPDF, que no posiciona tspan)
     */
    public function generar(array $serie, string $titulo, int $tamano = 360, ?array $comparacion = null, bool $dividirEtiquetas = true): string
    {
        $n = count($serie);
        if ($n < 3) {
            throw new InvalidArgumentException('El gráfico de radar requiere al menos tres ejes.');
        }

        // El lienzo es más ancho que alto para que las etiquetas laterales no se recorten.
        $ancho = (int) round($tamano * 1.6);
        $centroX = $ancho / 2;
        $centro = $tamano / 2;
        $radio = $tamano * 0.32;
        $etiquetas = array_keys($serie);
        $esc = fn (string $t) => htmlspecialchars($t, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $partes = [];
        $partes[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d" role="img" aria-labelledby="radar-titulo radar-desc">',
            $ancho, $tamano
        );
        $partes[] = '<title id="radar-titulo">'.$esc($titulo).'</title>';
        $descripcion = implode('; ', array_map(fn ($k, $v) => $k.': '.number_format($this->acotar($v), 2), $etiquetas, $serie));
        $partes[] = '<desc id="radar-desc">'.$esc($descripcion).'</desc>';
        $partes[] = sprintf('<g transform="translate(%.2f 0)">', $centroX - $centro);

        foreach ([25, 50, 75, 100] as $nivel) {
            $partes[] = sprintf('<polygon points="%s" fill="none" stroke="#c8ccd4" stroke-width="1"/>', $this->puntos(array_fill(0, $n, $nivel), $centro, $radio));
        }

        foreach ($etiquetas as $i => $etiqueta) {
            [$x, $y] = $this->coordenada($i, $n, 100, $centro, $radio);
            [$lx, $ly] = $this->coordenada($i, $n, 114, $centro, $radio);
            $coseno = cos(-M_PI / 2 + 2 * M_PI * $i / $n);
            $anclaje = $coseno > 0.2 ? 'start' : ($coseno < -0.2 ? 'end' : 'middle');
            $lineas = $dividirEtiquetas ? $this->dividir($etiqueta) : [$etiqueta];
            $desplazamiento = $ly < $centro ? -6 * (count($lineas) - 1) : 4;
            $tspans = '';
            foreach ($lineas as $k => $linea) {
                $tspans .= sprintf('<tspan x="%.2f" dy="%d">%s</tspan>', $lx, $k === 0 ? 0 : 13, $esc($linea));
            }
            $partes[] = sprintf('<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke="#c8ccd4" stroke-width="1"/>', $centro, $centro, $x, $y);
            $partes[] = sprintf(
                '<text x="%.2f" y="%.2f" font-size="11" font-family="Arial, sans-serif" text-anchor="%s" fill="#1f2937">%s</text>',
                $lx, $ly + $desplazamiento, $anclaje, $tspans
            );
        }

        if ($comparacion !== null) {
            $valores = array_map(fn ($e) => $this->acotar($comparacion[$e] ?? 0), $etiquetas);
            $partes[] = sprintf('<polygon points="%s" fill="#f59e0b" fill-opacity="0.18" stroke="#b45309" stroke-width="2" stroke-dasharray="5 3"/>', $this->puntos($valores, $centro, $radio));
        }

        $valores = array_map(fn ($v) => $this->acotar($v), array_values($serie));
        $partes[] = sprintf('<polygon points="%s" fill="#1d4ed8" fill-opacity="0.22" stroke="#1d4ed8" stroke-width="2"/>', $this->puntos($valores, $centro, $radio));

        foreach ($valores as $i => $valor) {
            [$x, $y] = $this->coordenada($i, $n, $valor, $centro, $radio);
            $partes[] = sprintf('<circle cx="%.2f" cy="%.2f" r="3" fill="#1d4ed8"/>', $x, $y);
        }

        $partes[] = '</g></svg>';

        return implode('', $partes);
    }

    /** Devuelve el SVG como URI de datos para incrustarlo como imagen (por ejemplo, en DomPDF). */
    public function comoDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** @return string[] etiqueta dividida en dos líneas cuando es extensa */
    private function dividir(string $etiqueta): array
    {
        if (mb_strlen($etiqueta) <= 14 || ! str_contains($etiqueta, ' ')) {
            return [$etiqueta];
        }
        $palabras = explode(' ', $etiqueta);
        $mitad = (int) ceil(count($palabras) / 2);

        return [implode(' ', array_slice($palabras, 0, $mitad)), implode(' ', array_slice($palabras, $mitad))];
    }

    private function acotar(float|int $valor): float
    {
        return max(0.0, min(100.0, (float) $valor));
    }

    /** @param float[] $valores */
    private function puntos(array $valores, float $centro, float $radio): string
    {
        $n = count($valores);
        $coordenadas = [];
        foreach (array_values($valores) as $i => $valor) {
            [$x, $y] = $this->coordenada($i, $n, $valor, $centro, $radio);
            $coordenadas[] = sprintf('%.2f,%.2f', $x, $y);
        }

        return implode(' ', $coordenadas);
    }

    /** @return array{0:float,1:float} */
    private function coordenada(int $indice, int $total, float $valor, float $centro, float $radio): array
    {
        $angulo = -M_PI / 2 + 2 * M_PI * $indice / $total;
        $distancia = $radio * $valor / 100;

        return [$centro + $distancia * cos($angulo), $centro + $distancia * sin($angulo)];
    }
}
