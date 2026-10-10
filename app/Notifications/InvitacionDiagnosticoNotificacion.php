<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Recordatorio para completar el diagnóstico (CU-05, flujo alterno). */
class InvitacionDiagnosticoNotificacion extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Complete su diagnóstico de competencias informáticas')
            ->line('Le invitamos a completar el diagnóstico de competencias informáticas. Tarda entre 30 y 45 minutos.')
            ->action('Ir a la plataforma', route('aspirante.panel'))
            ->salutation('Plataforma de Competencias Informáticas');
    }

    public function toArray(object $notifiable): array
    {
        return ['tipo' => 'invitacion_diagnostico'];
    }
}
