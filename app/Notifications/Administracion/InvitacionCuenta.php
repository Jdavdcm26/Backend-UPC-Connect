<?php

namespace App\Notifications\Administracion;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitación para definir la contraseña de una cuenta creada por el
 * administrador. El enlace abre la misma página de la SPA que la recuperación
 * de contraseña.
 */
class InvitacionCuenta extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] public string $token)
    {
        $this->onQueue('mail');
    }

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
        $enlace = config('app.frontend_url').'/restablecer-contrasena?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Te damos la bienvenida a UPC Connect')
            ->greeting('¡Hola!')
            ->line('La Universidad Popular del Cesar creó tu cuenta en UPC Connect. Para empezar, define tu contraseña.')
            ->action('Definir mi contraseña', $enlace)
            ->line('El enlace vence en 7 días. Si vence, usa «¿Olvidaste tu contraseña?» en la página de inicio de sesión.')
            ->salutation('El equipo de UPC Connect');
    }
}
