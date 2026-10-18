<?php

namespace App\Http\Requests\Vacantes;

use App\Enums\Vacantes\ModalidadVacante;
use App\Enums\Vacantes\TipoVacante;
use App\Models\Catalogos\Carrera;
use App\Models\Vacantes\Vacante;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GuardarVacanteRequest extends FormRequest
{
    /**
     * Publicar exige empresa aprobada; editar, que la vacante no esté cerrada.
     * Se autoriza antes de validar para que la empresa vea el motivo real.
     */
    public function authorize(): Response
    {
        $vacante = $this->route('vacantePropia');

        return $vacante instanceof Vacante
            ? Gate::inspect('update', $vacante)
            : Gate::inspect('create', Vacante::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoVacante::class)],
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'requisitos' => ['required', 'string', 'max:3000'],
            'ciudad' => ['required', 'string', 'max:80'],
            'modalidad' => ['required', Rule::enum(ModalidadVacante::class)],
            'fecha_cierre' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'carreras' => ['required', 'array', 'min:1', 'max:20', $this->carrerasValidas(...)],
            'carreras.*' => ['uuid', 'distinct'],
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
            'fecha_cierre.after_or_equal' => 'La fecha de cierre no puede ser anterior a hoy.',
            'carreras.required' => 'Elige al menos una carrera objetivo.',
            'carreras.min' => 'Elige al menos una carrera objetivo.',
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
            'titulo' => 'título',
            'descripcion' => 'descripción',
            'fecha_cierre' => 'fecha de cierre',
            'carreras' => 'carreras objetivo',
            'carreras.*' => 'carrera objetivo',
        ];
    }

    /**
     * Carreras activas de la universidad de la empresa. Al editar se conservan
     * las que la vacante ya tenía aunque se hayan desactivado.
     *
     * @param  array<mixed>  $value
     */
    private function carrerasValidas(string $attribute, mixed $value, Closure $fail): void
    {
        $ids = collect($value)->filter(fn (mixed $id): bool => is_string($id))->unique();
        $vacante = $this->route('vacantePropia');
        $yaAsignadas = $vacante instanceof Vacante ? $vacante->carreras()->pluck('carreras.id') : collect();

        $validas = Carrera::query()
            ->whereKey($ids->all())
            ->where(fn ($consulta) => $consulta->where('activa', true)->orWhereIn('id', $yaAsignadas->all()))
            ->count();

        if ($validas !== count($value)) {
            $fail('Elige carreras activas de tu universidad.');
        }
    }
}
