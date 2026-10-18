<?php

namespace App\Http\Requests\Postulaciones;

use App\Models\Postulaciones\Postulacion;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PostularseRequest extends FormRequest
{
    /**
     * Solo estudiantes y egresados con perfil. Se autoriza antes de validar
     * para que la persona vea el motivo real.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Postulacion::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mensaje' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
