<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Mensaje interno del administrador al aspirante (CU-05, paso 7). */
class MensajeInternoNotificacion extends Notification
{
    use Queueable;

    public function __construct(public string $mensaje)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['tipo' => 'mensaje_interno', 'mensaje' => $this->mensaje];
    }
}
