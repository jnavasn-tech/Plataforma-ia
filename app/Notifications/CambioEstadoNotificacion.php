<?php

namespace App\Notifications;

use App\Enums\EstadoPostulacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso al aspirante cuando RRHH cambia su estado a "apto" o "no apto" (CU-05). */
class CambioEstadoNotificacion extends Notification
{
    use Queueable;

    public function __construct(public EstadoPostulacion $estado, public string $puesto)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Actualización de su proceso de selección')
            ->line("Su estado en el proceso para el puesto \"{$this->puesto}\" fue actualizado a: {$this->estado->etiqueta()}.")
            ->line('Para más información, ingrese a la plataforma o comuníquese con Recursos Humanos de Grupo Ilimitado, S. A.')
            ->salutation('Plataforma de Competencias Informáticas');
    }

    public function toArray(object $notifiable): array
    {
        return ['tipo' => 'cambio_estado', 'estado' => $this->estado->value, 'puesto' => $this->puesto];
    }
}
