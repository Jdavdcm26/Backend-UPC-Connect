<?php

namespace App\Rules\Autenticacion;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Verifica con Cloudflare Turnstile el token que envía la SPA. Si Cloudflare
 * no responde, el CAPTCHA se rechaza: nunca se deja pasar sin verificar.
 */
class CaptchaValido implements ValidationRule
{
    public const string URL_VERIFICACION = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(private ?string $ip = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || ! $this->esValidoEnCloudflare($value)) {
            $fail('No pudimos comprobar que eres una persona. Completa de nuevo la verificación e inténtalo otra vez.');
        }
    }

    private function esValidoEnCloudflare(string $token): bool
    {
        try {
            $respuesta = Http::asForm()
                ->timeout(5)
                ->post(self::URL_VERIFICACION, [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $this->ip,
                ]);
        } catch (ConnectionException) {
            return false;
        }

        return $respuesta->successful() && $respuesta->json('success') === true;
    }
}
