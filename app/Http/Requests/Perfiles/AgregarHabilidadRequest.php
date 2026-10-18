<?php

namespace App\Http\Requests\Perfiles;

use App\Enums\Perfiles\NivelHabilidad;
use App\Models\Catalogos\Habilidad;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgregarHabilidadRequest extends FormRequest
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
            'habilidad_id' => ['required', 'uuid', Rule::exists(Habilidad::class, 'id')->where('activa', true)],
            'nivel' => ['nullable', Rule::enum(NivelHabilidad::class)],
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
            'habilidad_id.exists' => 'Elige una habilidad del catálogo.',
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
            'habilidad_id' => 'habilidad',
        ];
    }
}
