<?php

namespace App\Http\Requests\Vacantes;

use App\Enums\Vacantes\ModalidadVacante;
use App\Enums\Vacantes\TipoVacante;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuscarVacantesRequest extends FormRequest
{
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
            'q' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', Rule::enum(TipoVacante::class)],
            'modalidad' => ['nullable', Rule::enum(ModalidadVacante::class)],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'carrera_id' => ['nullable', 'uuid'],
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
            'q' => 'búsqueda',
            'carrera_id' => 'carrera',
        ];
    }
}
