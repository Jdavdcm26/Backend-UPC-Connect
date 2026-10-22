<?php

namespace App\Models\Publicaciones;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen adjunta a una publicación (HU-21). El archivo vive en el disco público.
 */
#[Table('publicacion_imagenes')]
#[Fillable(['ruta'])]
class PublicacionImagen extends Model
{
    use HasUuids;

    public const string DISCO = 'public';

    /**
     * @return BelongsTo<Publicacion, $this>
     */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(Publicacion::class);
    }

    public function url(): string
    {
        return Storage::disk(self::DISCO)->url($this->ruta);
    }
}
