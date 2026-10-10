<?php

namespace Database\Seeders;

use App\Enums\ClavePrompt;
use App\Models\AreaCompetencia;
use App\Models\ConfiguracionPrompt;
use App\Models\ModuloContenido;
use App\Models\Pregunta;
use App\Models\ProcesoSeleccion;
use App\Models\RecursoAprendizaje;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos iniciales del catálogo (sin datos personales): áreas, módulos, recursos internos,
 * banco de preguntas de demostración, prompts institucionales v1 y un proceso de selección de ejemplo.
 * Es idempotente: puede ejecutarse varias veces sin duplicar registros.
 */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $areas = $this->areas();
            $this->modulos($areas);
            $this->preguntasDiagnostico($areas);
            $this->prompts();
            ProcesoSeleccion::firstOrCreate(
                ['nombre' => 'Proceso de demostración 2026'],
                ['puesto' => 'Auxiliar administrativo', 'fecha_inicio' => now()->startOfYear()->toDateString(), 'activo' => true]
            );
        });
    }

    /** @return array<string,AreaCompetencia> */
    private function areas(): array
    {
        $resultado = [];
        foreach (require __DIR__.'/datos/areas.php' as $area) {
            $resultado[$area['codigo']] = AreaCompetencia::updateOrCreate(['codigo' => $area['codigo']], $area + ['activo' => true]);
        }

        return $resultado;
    }

    /** @param array<string,AreaCompetencia> $areas */
    private function modulos(array $areas): void
    {
        $contador = 0;
        foreach (require __DIR__.'/datos/modulos.php' as $datos) {
            $modulo = ModuloContenido::updateOrCreate(['codigo' => $datos['codigo']], [
                'id_area' => $areas[$datos['area']]->id_area,
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'],
                'nivel' => $datos['nivel'],
                'orden' => $datos['orden'],
                'activo' => true,
            ]);

            foreach ($datos['recursos'] as $recurso) {
                RecursoAprendizaje::updateOrCreate(
                    ['id_modulo' => $modulo->id_modulo, 'titulo' => $recurso['titulo']],
                    ['tipo' => $recurso['tipo'], 'contenido' => $recurso['contenido'], 'duracion_minutos' => $recurso['duracion'], 'estado' => 'activo']
                );
            }

            $dificultad = ['basico' => 1, 'intermedio' => 2, 'avanzado' => 3][$datos['nivel']];
            foreach ($datos['preguntas'] as [$enunciado, $opciones]) {
                $this->pregunta($areas[$datos['area']]->id_area, $modulo->id_modulo, 'evaluacion_modulo', $dificultad, $enunciado, $opciones, $contador++);
            }
        }
    }

    /** @param array<string,AreaCompetencia> $areas */
    private function preguntasDiagnostico(array $areas): void
    {
        $contador = 0;
        foreach (require __DIR__.'/datos/preguntas_diagnostico.php' as $codigo => $porDificultad) {
            foreach ($porDificultad as $dificultad => $preguntas) {
                foreach ($preguntas as [$enunciado, $opciones]) {
                    $this->pregunta($areas[$codigo]->id_area, null, 'diagnostico', $dificultad, $enunciado, $opciones, $contador++);
                }
            }
        }
    }

    /**
     * Crea la pregunta si no existe. La primera opción del arreglo es la correcta; el orden se rota
     * de forma determinística para que la respuesta correcta no ocupe siempre la misma posición.
     *
     * @param  string[]  $opciones
     */
    private function pregunta(int $idArea, ?int $idModulo, string $uso, int $dificultad, string $enunciado, array $opciones, int $indice): void
    {
        if (Pregunta::where('enunciado', $enunciado)->where('uso', $uso)->exists()) {
            return;
        }

        $pregunta = Pregunta::create([
            'id_area' => $idArea, 'id_modulo' => $idModulo, 'uso' => $uso, 'dificultad' => $dificultad,
            'enunciado' => $enunciado, 'activo' => true, 'version' => 1,
        ]);

        $n = count($opciones);
        $desplazamiento = $indice % $n;
        $rotadas = array_merge(array_slice($opciones, $desplazamiento), array_slice($opciones, 0, $desplazamiento));
        $posicionCorrecta = ($n - $desplazamiento) % $n;

        foreach ($rotadas as $i => $texto) {
            $pregunta->opciones()->create(['texto' => $texto, 'es_correcta' => $i === $posicionCorrecta, 'orden' => $i + 1]);
        }
    }

    private function prompts(): void
    {
        foreach (ClavePrompt::cases() as $clave) {
            ConfiguracionPrompt::firstOrCreate(
                ['clave' => $clave->value, 'version' => 1],
                [
                    'contenido' => file_get_contents(resource_path("prompts/{$clave->value}.v1.md")),
                    'descripcion_cambio' => 'Versión inicial del prompt institucional (Capítulo V, Anexo K).',
                    'activo' => true,
                    'activado_en' => now(),
                ]
            );
        }
    }
}
