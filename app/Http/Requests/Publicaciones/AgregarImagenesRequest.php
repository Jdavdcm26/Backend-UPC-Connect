<?php

namespace App\Http\Requests\Publicaciones;

use App\Models\Publicaciones\Publicacion;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AgregarImagenesRequest extends FormRequest
{
    /**
     * Solo el autor agrega imágenes a su publicación.
     */
    public function authorize(): Response
    {
        $publicacion = $this->route('publicacion');
        $vista = Gate::inspect('view', $publicacion);

        return $vista->allowed() ? Gate::inspect('update', $publicacion) : $vista;
    }

    /**
     * Entre las que ya tiene y las nuevas, como máximo cuatro (HU-21).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Publicacion $publicacion */
        $publicacion = $this->route('publicacion');
        $disponibles = Publicacion::MAXIMO_IMAGENES - $publicacion->imagenes()->count();

        return [
            'imagenes' => ['required', 'array', 'min:1', 'max:'.$disponibles],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
            'imagenes.max' => 'Una publicación tiene como máximo '.Publicacion::MAXIMO_IMAGENES.' imágenes.',
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
            'imagenes' => 'imágenes',
            'imagenes.*' => 'imagen',
        ];
    }
}
