<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/** Correo de confirmación de cuenta (CU-01, pasos 5 y 6). */
class VerificarCorreoNotificacion extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage())
            ->subject('Confirme su correo electrónico')
            ->greeting('Bienvenido(a) a la Plataforma de Competencias Informáticas')
            ->line('Para activar su cuenta, confirme su correo electrónico con el siguiente botón.')
            ->action('Confirmar correo', $url)
            ->line('Si usted no creó esta cuenta, puede ignorar este mensaje.')
            ->salutation('Plataforma de Competencias Informáticas');
    }
}
