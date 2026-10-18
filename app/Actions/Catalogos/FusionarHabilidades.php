<?php

namespace App\Actions\Catalogos;

use App\Models\Catalogos\Habilidad;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FusionarHabilidades
{
    /**
     * Pasa a la habilidad destino los perfiles que tenían la de origen, sin
     * duplicar a quienes ya tenían ambas (se conserva el nivel del destino), y
     * desactiva la de origen. Todo ocurre en una sola transacción.
     *
     * @return int Perfiles que tenían la habilidad de origen.
     */
    public function handle(Habilidad $origen, Habilidad $destino): int
    {
        if ($origen->is($destino)) {
            throw new InvalidArgumentException('Una habilidad no se puede fusionar consigo misma.');
        }

        return DB::transaction(function () use ($origen, $destino): int {
            $pivote = DB::table('perfil_habilidad');
            $perfilesAfectados = (clone $pivote)->where('habilidad_id', $origen->id)->count();

            (clone $pivote)
                ->where('habilidad_id', $origen->id)
                ->whereIn('perfil_id', DB::table('perfil_habilidad')->select('perfil_id')->where('habilidad_id', $destino->id))
                ->delete();

            (clone $pivote)
                ->where('habilidad_id', $origen->id)
                ->update(['habilidad_id' => $destino->id, 'updated_at' => now()]);

            $origen->update(['activa' => false]);

            return $perfilesAfectados;
        });
    }
}
