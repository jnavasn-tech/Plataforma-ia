<?php

namespace App\Services\Prompts;

use App\Enums\ClavePrompt;
use App\Models\ConfiguracionPrompt;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de prompts institucionales versionados (RF-08, CU-09 propuesto).
 * Las versiones no se editan: cada cambio crea una nueva versión, lo que conserva la trazabilidad
 * entre cada resultado generado y la versión exacta del prompt utilizada.
 */
class ServicioPrompts
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    public function crearVersion(ClavePrompt $clave, string $contenido, ?string $descripcion, Usuario $administrador, bool $activar = false): ConfiguracionPrompt
    {
        $prompt = DB::transaction(function () use ($clave, $contenido, $descripcion, $administrador) {
            $version = (int) ConfiguracionPrompt::where('clave', $clave->value)->lockForUpdate()->max('version') + 1;

            return ConfiguracionPrompt::create([
                'clave' => $clave,
                'version' => $version,
                'contenido' => $contenido,
                'descripcion_cambio' => $descripcion,
                'activo' => false,
                'creado_por' => $administrador->id_usuario,
            ]);
        });

        $this->bitacora->registrar('prompt_version_creada', $administrador, $prompt, ['clave' => $clave->value, 'version' => $prompt->version]);

        if ($activar) {
            $this->activar($prompt, $administrador);
        }

        return $prompt;
    }

    public function activar(ConfiguracionPrompt $prompt, Usuario $administrador): void
    {
        DB::transaction(function () use ($prompt) {
            ConfiguracionPrompt::where('clave', $prompt->clave->value)->update(['activo' => false]);
            $prompt->update(['activo' => true, 'activado_en' => now()]);
        });

        $this->bitacora->registrar('prompt_activado', $administrador, $prompt, ['clave' => $prompt->clave->value, 'version' => $prompt->version]);
    }
}
