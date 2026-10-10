<?php

namespace App\Services\Aspirantes;

use App\Enums\EstadoPostulacion;
use App\Models\HistorialEstado;
use App\Models\Postulacion;
use App\Models\ProcesoSeleccion;
use App\Models\Usuario;
use App\Notifications\CambioEstadoNotificacion;
use App\Notifications\InvitacionDiagnosticoNotificacion;
use App\Notifications\MensajeInternoNotificacion;
use App\Services\Bitacora\RegistradorBitacora;
use Illuminate\Support\Facades\DB;

/**
 * Caso de uso CU-05 "Gestionar aspirantes". El cambio de estado (pendiente, en formación, apto, no apto)
 * es una decisión humana del administrador de RRHH; la plataforma solo aporta información de apoyo.
 */
class ServicioAspirantes
{
    public function __construct(private readonly RegistradorBitacora $bitacora)
    {
    }

    public function cambiarEstado(Postulacion $postulacion, EstadoPostulacion $nuevo, Usuario $administrador, ?string $comentario): void
    {
        $anterior = $postulacion->estado;
        if ($anterior === $nuevo) {
            return;
        }

        DB::transaction(function () use ($postulacion, $anterior, $nuevo, $administrador, $comentario) {
            $postulacion->update(['estado' => $nuevo, 'actualizado_por' => $administrador->id_usuario]);
            HistorialEstado::create([
                'id_postulacion' => $postulacion->id_postulacion,
                'estado_anterior' => $anterior,
                'estado_nuevo' => $nuevo,
                'id_admin' => $administrador->id_usuario,
                'comentario' => $comentario,
            ]);
        });

        if ($nuevo->requiereNotificacion()) {
            $postulacion->usuario->notify(new CambioEstadoNotificacion($nuevo, $postulacion->proceso->puesto));
        }

        $this->bitacora->registrar('estado_postulacion_actualizado', $administrador, $postulacion, [
            'estado_anterior' => $anterior->value,
            'estado_nuevo' => $nuevo->value,
        ]);
    }

    public function asignarProceso(Usuario $aspirante, ProcesoSeleccion $proceso, Usuario $administrador): Postulacion
    {
        $postulacion = Postulacion::firstOrCreate(
            ['id_usuario' => $aspirante->id_usuario, 'id_proceso' => $proceso->id_proceso],
            ['estado' => EstadoPostulacion::Pendiente, 'actualizado_por' => $administrador->id_usuario]
        );
        $this->bitacora->registrar('aspirante_asignado_proceso', $administrador, $postulacion);

        return $postulacion;
    }

    public function enviarMensaje(Usuario $aspirante, string $mensaje, Usuario $administrador): void
    {
        $aspirante->notify(new MensajeInternoNotificacion($mensaje));
        $this->bitacora->registrar('mensaje_interno_enviado', $administrador, $aspirante, ['longitud' => mb_strlen($mensaje)]);
    }

    public function reenviarInvitacion(Usuario $aspirante, Usuario $administrador): void
    {
        $aspirante->notify(new InvitacionDiagnosticoNotificacion());
        $this->bitacora->registrar('invitacion_reenviada', $administrador, $aspirante);
    }
}
