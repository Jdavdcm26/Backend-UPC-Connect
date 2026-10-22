<?php

namespace App\Actions\Publicaciones;

use App\Models\Publicaciones\Publicacion;
use App\Models\Publicaciones\PublicacionImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AgregarImagenes
{
    /**
     * Guarda las imágenes en el disco público y las asocia a la publicación
     * (HU-21). Si algo falla, borra los archivos que alcanzó a guardar para
     * no dejar huérfanos.
     *
     * @param  list<UploadedFile>  $imagenes
     */
    public function handle(Publicacion $publicacion, array $imagenes): void
    {
        $guardadas = [];

        try {
            foreach ($imagenes as $imagen) {
                $ruta = $imagen->store("publicaciones/{$publicacion->id}", PublicacionImagen::DISCO);

                if ($ruta === false) {
                    throw new RuntimeException('No se pudo guardar la imagen.');
                }

                $guardadas[] = $ruta;
                $publicacion->imagenes()->create(['ruta' => $ruta]);
            }
        } catch (Throwable $error) {
            Storage::disk(PublicacionImagen::DISCO)->delete($guardadas);

            throw $error;
        }
    }
}
