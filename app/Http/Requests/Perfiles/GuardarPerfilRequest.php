<?php

namespace App\Http\Requests\Perfiles;

use App\Enums\Autenticacion\Rol;
use App\Models\Catalogos\Carrera;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarPerfilRequest extends FormRequest
{
    public const int ANIO_FUNDACION_UPC = 1976;

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
        $esEgresado = $this->user()->rol === Rol::Egresado;

        return [
            'nombres' => ['required', 'string', 'max:80'],
            'apellidos' => ['required', 'string', 'max:80'],
            'titular' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'carrera_id' => ['required', 'uuid', $this->carreraValida(...)],
            'semestre' => [Rule::requiredIf(! $esEgresado), Rule::prohibitedIf($esEgresado), 'nullable', 'integer', 'between:1,12'],
            'anio_grado' => [Rule::requiredIf($esEgresado), Rule::prohibitedIf(! $esEgresado), 'nullable', 'integer', 'between:'.self::ANIO_FUNDACION_UPC.','.now()->year],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'intereses' => ['nullable', 'array', 'max:10'],
            'intereses.*' => ['string', 'distinct:ignore_case', 'max:50'],
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
            'semestre.prohibited' => 'Los egresados no registran semestre; indica tu año de grado.',
            'anio_grado.prohibited' => 'Solo los egresados registran año de grado.',
            'intereses.max' => 'Puedes agregar hasta :max intereses.',
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
            'carrera_id' => 'carrera',
            'anio_grado' => 'año de grado',
            'descripcion' => 'descripción',
            'intereses.*' => 'interés',
        ];
    }

    /**
     * La carrera debe ser activa y de la universidad del usuario. Se acepta
     * también la que el perfil ya tiene aunque se haya desactivado (HU-53).
     */
    private function carreraValida(string $attribute, mixed $value, Closure $fail): void
    {
        $carreraActual = $this->user()->perfil()->value('carrera_id');

        $valida = Carrera::query()
            ->whereKey($value)
            ->where(fn ($consulta) => $consulta->where('activa', true)->orWhere('id', $carreraActual))
            ->exists();

        if (! $valida) {
            $fail('Elige una carrera activa de tu universidad.');
        }
    }
}
