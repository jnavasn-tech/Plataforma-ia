<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Enlace de restablecimiento de contraseña (CU-01 y CU-08 flujo alterno). */
class RestablecerContrasenaNotificacion extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $minutos = config('auth.passwords.users.expire');

        return (new MailMessage())
            ->subject('Restablecimiento de contraseña')
            ->line('Recibimos una solicitud para restablecer la contraseña de su cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("El enlace vence en {$minutos} minutos.")
            ->line('Si usted no realizó la solicitud, no es necesario hacer nada.')
            ->salutation('Plataforma de Competencias Informáticas');
    }
}
