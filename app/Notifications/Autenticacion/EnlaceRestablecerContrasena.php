<?php

namespace App\Notifications\Autenticacion;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo con el enlace temporal para restablecer la contraseña, en español y
 * por la cola "mail". El enlace abre la página de la SPA.
 */
class EnlaceRestablecerContrasena extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] string $token)
    {
        parent::__construct($token);

        $this->onQueue('mail');
    }

    /**
     * Get the reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     */
    protected function resetUrl($notifiable): string
    {
        return config('app.frontend_url').'/restablecer-contrasena?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    /**
     * Get the reset password notification mail message for the given URL.
     *
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Restablece tu contraseña de UPC Connect')
            ->greeting('¡Hola!')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line('El enlace vence en '.config('auth.passwords.users.expire').' minutos y solo se puede usar una vez.')
            ->line('Si no lo solicitaste, ignora este mensaje: tu contraseña no cambiará.')
            ->salutation('El equipo de UPC Connect');
    }
}
