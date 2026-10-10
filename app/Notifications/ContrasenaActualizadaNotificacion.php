<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmación de cambio de contraseña (CU-08, resultado esperado). */
class ContrasenaActualizadaNotificacion extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Su contraseña fue actualizada')
            ->line('La contraseña de su cuenta se actualizó correctamente y se cerraron las sesiones en otros dispositivos.')
            ->line('Si usted no realizó este cambio, restablezca su contraseña de inmediato y comuníquese con el administrador.')
            ->salutation('Plataforma de Competencias Informáticas');
    }
}
