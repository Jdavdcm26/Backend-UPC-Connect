<?php

namespace App\Notifications\Autenticacion;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Correo de verificación en español, enviado por la cola "mail". El enlace
 * abre la SPA, que llama a la API con los parámetros firmados.
 */
class VerificarCorreo extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('mail');
    }

    /**
     * Enlace a la página de verificación de la SPA. La firma es relativa a la
     * ruta de la API, así que no depende del dominio por el que llegue.
     *
     * @param  mixed  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        $rutaFirmada = URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => hash('sha256', $notifiable->getEmailForVerification()),
            ],
            absolute: false,
        );

        parse_str((string) parse_url($rutaFirmada, PHP_URL_QUERY), $firma);

        return config('app.frontend_url').'/verificar-correo?'.http_build_query([
            'id' => $notifiable->getKey(),
            'hash' => hash('sha256', $notifiable->getEmailForVerification()),
            ...$firma,
        ]);
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
