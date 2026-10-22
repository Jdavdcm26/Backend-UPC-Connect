<?php

namespace App\Http\Controllers\Networking;

use App\Actions\Networking\BloquearUsuario;
use App\Http\Controllers\Controller;
use App\Http\Resources\Networking\UsuarioBloqueadoResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Bloqueos del usuario con sesión (HU-31).
 */
class BloqueoController extends Controller
{
    /**
     * Cuentas que el usuario bloqueó, las más recientes primero, para poder
     * desbloquearlas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $bloqueados = $request->user()->bloqueados()
            ->with(['perfil', 'empresa'])
            ->orderByPivot('created_at', 'desc')
            ->get();

        return UsuarioBloqueadoResource::collection($bloqueados);
    }

    public function store(Request $request, User $usuario, BloquearUsuario $bloquearUsuario): Response
    {
        Gate::authorize('bloquear', $usuario);

        $bloquearUsuario->handle($request->user(), $usuario);

        return response()->noContent();
    }

    public function destroy(Request $request, User $usuario): Response
    {
        $request->user()->bloqueados()->detach($usuario->id);

        return response()->noContent();
    }
}
