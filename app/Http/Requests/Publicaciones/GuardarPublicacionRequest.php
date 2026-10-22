<?php

namespace App\Http\Requests\Publicaciones;

use App\Enums\Publicaciones\TipoPublicacion;
use App\Enums\Vacantes\ModalidadVacante;
use App\Models\Publicaciones\Publicacion;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GuardarPublicacionRequest extends FormRequest
{
    /**
     * Campos de la plantilla de cada tipo (HU-19, HU-20).
     */
    private const array CAMPOS_DETALLE = [
        TipoPublicacion::Servicio->value => ['area', 'modalidad', 'disponibilidad'],
        TipoPublicacion::BusquedaEquipo->value => ['proyecto', 'perfiles_requeridos', 'cupos'],
    ];

    /**
     * Publicar exige ser estudiante o egresado con perfil; editar, ser el
     * autor. Una publicación que el usuario no puede ver responde 404.
     */
    public function authorize(): Response
    {
        $publicacion = $this->route('publicacion');

        if (! $publicacion instanceof Publicacion) {
            return Gate::inspect('create', Publicacion::class);
        }

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
        $publicacion = $this->route('publicacion');
        $categoriaActual = $publicacion instanceof Publicacion ? $publicacion->categoria_id : null;

        $reglas = [
            'categoria_id' => ['required', 'uuid', Rule::exists('categorias', 'id')->where(
                fn ($consulta) => $consulta->where(fn ($categorias) => $categorias->where('activa', true)->orWhere('id', $categoriaActual)),
            )],
            'contenido' => ['required', 'string', 'max:3000'],
        ];

        if (! $publicacion instanceof Publicacion) {
            $reglas['tipo'] = ['required', Rule::enum(TipoPublicacion::class)];
            $reglas['imagenes'] = ['nullable', 'array', 'max:'.Publicacion::MAXIMO_IMAGENES];
            $reglas['imagenes.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        return [...$reglas, ...$this->reglasDeDetalles()];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoria_id.exists' => 'Elige una categoría activa.',
            'imagenes.max' => 'Puedes adjuntar como máximo '.Publicacion::MAXIMO_IMAGENES.' imágenes.',
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
            'imagenes' => 'imágenes',
            'imagenes.*' => 'imagen',
            'detalles' => 'detalles',
            'detalles.area' => 'área',
            'detalles.modalidad' => 'modalidad',
            'detalles.disponibilidad' => 'disponibilidad',
            'detalles.proyecto' => 'proyecto',
            'detalles.perfiles_requeridos' => 'perfiles requeridos',
            'detalles.cupos' => 'cupos',
        ];
    }

    /**
     * Al publicar el tipo viene en la solicitud; al editar es el de la
     * publicación, que no cambia.
     */
    public function tipo(): ?TipoPublicacion
    {
        $publicacion = $this->route('publicacion');

        return $publicacion instanceof Publicacion
            ? $publicacion->tipo
            : TipoPublicacion::tryFrom((string) $this->input('tipo'));
    }

    /**
     * Datos validados listos para guardar: los detalles solo con los campos de
     * la plantilla del tipo, y nulos en una publicación general.
     *
     * @return array{categoria_id: string, contenido: string, detalles: array<string, mixed>|null}
     */
    public function datos(): array
    {
        $campos = self::CAMPOS_DETALLE[$this->tipo()?->value] ?? [];
        $detalles = $campos === [] ? null : Arr::only($this->validated('detalles'), $campos);

        if (isset($detalles['cupos'])) {
            $detalles['cupos'] = (int) $detalles['cupos'];
        }

        return [
            'categoria_id' => $this->validated('categoria_id'),
            'contenido' => $this->validated('contenido'),
            'detalles' => $detalles,
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function reglasDeDetalles(): array
    {
        return match ($this->tipo()) {
            TipoPublicacion::Servicio => [
                'detalles' => ['required', 'array'],
                'detalles.area' => ['required', 'string', 'max:100'],
                'detalles.modalidad' => ['required', Rule::enum(ModalidadVacante::class)],
                'detalles.disponibilidad' => ['required', 'string', 'max:150'],
            ],
            TipoPublicacion::BusquedaEquipo => [
                'detalles' => ['required', 'array'],
                'detalles.proyecto' => ['required', 'string', 'max:150'],
                'detalles.perfiles_requeridos' => ['required', 'string', 'max:500'],
                'detalles.cupos' => ['required', 'integer', 'min:1', 'max:20'],
            ],
            default => [],
        };
    }
}
