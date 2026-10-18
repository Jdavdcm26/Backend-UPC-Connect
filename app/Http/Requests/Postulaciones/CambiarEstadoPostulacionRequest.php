<?php

namespace App\Http\Requests\Postulaciones;

use App\Enums\Postulaciones\EstadoPostulacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoPostulacionRequest extends FormRequest
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
            'estado' => ['required', Rule::enum(EstadoPostulacion::class)->only(EstadoPostulacion::deGestion())],
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
            'estado.enum' => 'Elige uno de estos estados: Recibida, En revisión, Entrevista, Seleccionado o No seleccionado.',
        ];
    }
}
