<?php

namespace App\Http\Requests\Publicaciones;

use App\Models\Publicaciones\Publicacion;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class CambiarAperturaRequest extends FormRequest
{
    /**
     * Solo el autor abre o cierra su publicación.
     */
    public function authorize(): Response
    {
        $publicacion = $this->route('publicacion');
        $vista = Gate::inspect('view', $publicacion);

        return $vista->allowed() ? Gate::inspect('update', $publicacion) : $vista;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'abierta' => ['required', 'boolean'],
        ];
    }

    /**
     * Una publicación general no se abre ni se cierra: no ofrece nada.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Publicacion $publicacion */
                $publicacion = $this->route('publicacion');

                if (! $publicacion->tipo->sePuedeCerrar()) {
                    $validator->errors()->add('abierta', 'Solo los servicios y las búsquedas de equipo se abren o se cierran.');
                }
            },
        ];
    }
}
