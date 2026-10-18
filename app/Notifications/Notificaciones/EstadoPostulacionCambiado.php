<?php

namespace App\Notifications\Notificaciones;

use App\Enums\Postulaciones\EstadoPostulacion;
use App\Models\Postulaciones\Postulacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo al candidato con el nuevo estado de su postulación. Los nombres de
 * estado son los mismos que muestra la SPA.
 */
class EstadoPostulacionCambiado extends Notification
{
    use Queueable;

    public function __construct(
        public Postulacion $postulacion,
        public EstadoPostulacion $estado,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $vacante = $this->postulacion->vacante;

        return (new MailMessage)
            ->subject("Tu postulación a «{$vacante->titulo}» cambió de estado")
            ->greeting('¡Hola!')
            ->line("{$vacante->empresa->razon_social} actualizó tu postulación a la vacante «{$vacante->titulo}».")
            ->line("Nuevo estado: **{$this->estado->getLabel()}**.")
            ->action('Ver mis postulaciones', config('app.frontend_url').'/mis-postulaciones')
            ->salutation('El equipo de UPC Connect');
    }
}
