<?php

namespace App\Notifications;

use App\Models\RutaAprendizaje;
use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Aviso a RRHH: el aspirante completó su ruta y está listo para la evaluación final (CU-04). */
class RutaCompletadaNotificacion extends Notification
{
    use Queueable;

    public function __construct(public Usuario $aspirante, public RutaAprendizaje $ruta)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['tipo' => 'ruta_completada', 'id_usuario' => $this->aspirante->id_usuario, 'id_ruta' => $this->ruta->id_ruta];
    }
}
