<?php

namespace App\Http\Controllers\Vacantes;

use App\Actions\Vacantes\GuardarVacante;
use App\Enums\Vacantes\EstadoVacante;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vacantes\GuardarVacanteRequest;
use App\Http\Resources\Vacantes\VacanteResource;
use App\Models\Vacantes\Vacante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Vacantes de la empresa con sesión (HU-41, HU-42). Las vacantes de otra
 * empresa responden 404 para no revelar que existen.
 */
class EmpresaVacanteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['estado' => ['nullable', Rule::enum(EstadoVacante::class)]]);

        $vacantes = $request->user()->empresa->vacantes()
            ->with('carreras')
            ->withCount('postulaciones as postulaciones_total')
            ->when($request->query('estado'), fn ($consulta, string $estado) => $consulta->where('estado', $estado))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(15);

        return VacanteResource::collection($vacantes);
    }

    public function store(GuardarVacanteRequest $request, GuardarVacante $guardarVacante): JsonResponse
    {
        $vacante = $guardarVacante->handle($request->user()->empresa, $request->validated());

        return (new VacanteResource($vacante->load('carreras')))->response()->setStatusCode(201);
    }

    public function show(Vacante $vacantePropia): VacanteResource
    {
        return new VacanteResource($vacantePropia->load('carreras')->loadCount('postulaciones as postulaciones_total'));
    }

    public function update(GuardarVacanteRequest $request, Vacante $vacantePropia, GuardarVacante $guardarVacante): VacanteResource
    {
        $vacante = $guardarVacante->handle($request->user()->empresa, $request->validated(), $vacantePropia);

        return new VacanteResource($vacante->load('carreras'));
    }
}
