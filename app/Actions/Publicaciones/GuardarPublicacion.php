<?php

namespace App\Actions\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Enums\Publicaciones\TipoPublicacion;
use App\Models\Publicaciones\Publicacion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class GuardarPublicacion
{
    public function __construct(private AgregarImagenes $agregarImagenes) {}

    /**
     * Crea una publicación visible en la universidad del autor, con sus
     * imágenes (HU-18 a HU-21), o actualiza el texto, la categoría y los
     * detalles de una existente y la marca como editada (HU-23). El tipo no
     * cambia al editar.
     *
     * @param  array{categoria_id: string, contenido: string, detalles: array<string, mixed>|null}  $datos
     * @param  list<UploadedFile>  $imagenes
     */
    public function handle(User $autor, array $datos, ?TipoPublicacion $tipo = null, ?Publicacion $publicacion = null, array $imagenes = []): Publicacion
    {
        if ($publicacion !== null) {
            $publicacion->fill($datos);

            if ($publicacion->isDirty()) {
                $publicacion->editada_en = now();
                $publicacion->save();
            }

            return $publicacion;
        }

        return DB::transaction(function () use ($autor, $datos, $tipo, $imagenes): Publicacion {
            $publicacion = $autor->publicaciones()->make($datos)->forceFill([
                'universidad_id' => $autor->universidad_id,
                'tipo' => $tipo ?? TipoPublicacion::General,
                'abierta' => true,
                'estado' => EstadoModeracion::Visible,
            ]);
            $publicacion->save();

            $this->agregarImagenes->handle($publicacion, $imagenes);

            return $publicacion;
        });
    }
}
