<?php

namespace App\Notifications\Autenticacion;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de verificación en español, enviado por la cola "mail".
 */
class VerificarCorreo extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('mail');
    }

    /**
     * Get the verify email notification mail message for the given URL.
     *
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Activa tu cuenta de UPC Connect')
            ->greeting('¡Hola!')
            ->line('Para activar tu cuenta, confirma que este es tu correo institucional.')
            ->action('Activar mi cuenta', $url)
            ->line('Si no creaste una cuenta en UPC Connect, puedes ignorar este mensaje.')
            ->salutation('El equipo de UPC Connect');
    }
}
