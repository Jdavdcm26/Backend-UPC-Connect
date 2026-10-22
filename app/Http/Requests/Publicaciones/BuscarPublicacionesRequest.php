<?php

namespace App\Http\Requests\Publicaciones;

use App\Enums\Publicaciones\TipoPublicacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuscarPublicacionesRequest extends FormRequest
{
    /**
     * La ruta ya exige poder ver el feed.
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
            'alcance' => ['nullable', Rule::in(['comunidad', 'seguidos'])],
            'tipo' => ['nullable', Rule::enum(TipoPublicacion::class)],
            'categoria_id' => ['nullable', 'uuid'],
            'autor_id' => ['nullable', 'uuid'],
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
            'categoria_id' => 'categoría',
            'autor_id' => 'autor',
        ];
    }
}
