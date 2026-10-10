<?php

namespace App\Services\Catalogo;

use App\Enums\EstadoRecurso;
use App\Models\RecursoAprendizaje;
use App\Models\Usuario;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Caso de uso CU-07 "Gestionar catálogo de recursos". */
class ServicioRecursos
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    /** Verifica que la URL responda (HEAD y, si no se admite, GET) dentro del tiempo configurado. */
    public function urlAccesible(string $url): bool
    {
        if (! config('plataforma.recursos.verificar_url')) {
            return true;
        }
        $timeout = (int) config('plataforma.recursos.timeout_verificacion_segundos', 10);

        try {
            $respuesta = Http::timeout($timeout)->withoutRedirecting()->head($url);
            if ($respuesta->status() === 405 || $respuesta->status() === 403) {
                $respuesta = Http::timeout($timeout)->get($url);
            }

            return $respuesta->successful() || $respuesta->redirect();
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array<string,mixed> $datos */
    public function crear(array $datos, Usuario $administrador, bool $urlAccesible): RecursoAprendizaje
    {
        $recurso = new RecursoAprendizaje($datos);
        $recurso->estado = $urlAccesible ? EstadoRecurso::Activo : EstadoRecurso::PendienteVerificacion;
        $recurso->creado_por = $administrador->id_usuario;
        $recurso->save();

        $this->bitacora->registrar('recurso_creado', $administrador, $recurso, ['estado' => $recurso->estado->value]);

        return $recurso;
    }

    /** @param array<string,mixed> $datos */
    public function actualizar(RecursoAprendizaje $recurso, array $datos, Usuario $administrador): RecursoAprendizaje
    {
        $recurso->fill($datos)->save();
        $this->bitacora->registrar('recurso_actualizado', $administrador, $recurso, ['estado' => $recurso->estado->value]);

        return $recurso;
    }
}
