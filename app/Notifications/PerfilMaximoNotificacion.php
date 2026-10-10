<?php

namespace App\Notifications;

use App\Models\PerfilCompetencia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Aviso a RRHH: un aspirante superó el umbral en todas las áreas (CU-03, flujo alterno). */
class PerfilMaximoNotificacion extends Notification
{
    use Queueable;

    public function __construct(public PerfilCompetencia $perfil)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['tipo' => 'perfil_maximo', 'id_perfil' => $this->perfil->id_perfil, 'id_usuario' => $this->perfil->diagnostico->id_usuario];
    }
}
