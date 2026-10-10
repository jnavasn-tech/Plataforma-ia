<?php

namespace App\Notifications;

use App\Models\RecursoAprendizaje;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Alerta al administrador para actualizar el enlace de un recurso (CU-04, flujo alterno). */
class RecursoNoDisponibleNotificacion extends Notification
{
    use Queueable;

    public function __construct(public RecursoAprendizaje $recurso)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['tipo' => 'recurso_no_disponible', 'id_recurso' => $this->recurso->id_recurso, 'titulo' => $this->recurso->titulo];
    }
}
