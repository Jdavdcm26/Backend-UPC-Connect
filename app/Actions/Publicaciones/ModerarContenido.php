<?php

namespace App\Actions\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Models\Administracion\Auditoria;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;

class ModerarContenido
{
    /**
     * Oculta o vuelve a mostrar una publicación o un comentario y lo deja en
     * la auditoría con el motivo (RF-58). Lo oculto sale del feed y de los
     * comentarios, pero se conserva para revisarlo.
     */
    public function handle(Publicacion|Comentario $contenido, EstadoModeracion $estado, ?string $motivo = null): void
    {
        $contenido->forceFill(['estado' => $estado])->save();

        $entidad = $contenido instanceof Publicacion ? 'publicacion' : 'comentario';
        $accion = $estado === EstadoModeracion::Oculto ? 'ocultado' : 'mostrado';

        Auditoria::registrar("{$entidad}.{$accion}", $contenido, array_filter(['motivo' => $motivo]));
    }
}
