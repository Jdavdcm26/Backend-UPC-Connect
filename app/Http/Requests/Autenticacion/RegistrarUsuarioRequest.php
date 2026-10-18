<?php

namespace App\Http\Requests\Autenticacion;

use App\Models\Autenticacion\Universidad;
use App\Rules\Autenticacion\CaptchaValido;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistrarUsuarioRequest extends FormRequest
{
    private ?Universidad $universidad = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->universidad = Universidad::query()
                        ->where('activa', true)
                        ->where('dominio_correo', Str::after((string) $value, '@'))
                        ->first();

                    if ($this->universidad === null) {
                        $fail('Debes registrarte con el correo institucional de tu universidad, por ejemplo nombre@unicesar.edu.co.');
                    }
                },
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'acepta_politica' => ['accepted'],
            'version_politica' => ['required', 'string', Rule::in([config('app.version_politica_datos')])],
            'captcha' => ['required', 'string', new CaptchaValido($this->ip())],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'acepta_politica.accepted' => 'Debes aceptar la política de tratamiento de datos para crear tu cuenta.',
            'version_politica.required' => 'Debes aceptar la política de tratamiento de datos para crear tu cuenta.',
            'version_politica.in' => 'La política de tratamiento de datos cambió. Recarga la página, revísala y acéptala de nuevo.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'captcha' => 'verificación de seguridad',
        ];
    }

    /**
     * La universidad cuyo dominio coincide con el correo, resuelta al validar.
     */
    public function universidad(): Universidad
    {
        return $this->universidad;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }
}
