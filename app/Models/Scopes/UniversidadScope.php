<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limita las consultas a la universidad del usuario autenticado.
 *
 * Sin usuario autenticado (inicio de sesión, registro, consola y colas) no
 * filtra: el correo es único en toda la plataforma y esas operaciones deben
 * poder encontrar la cuenta antes de saber a qué universidad pertenece.
 * Se usa hasUser() y no user() para no resolver la sesión desde aquí, lo
 * que provocaría una consulta recursiva sobre users.
 */
class UniversidadScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $guard = Auth::guard();

        if (! $guard->hasUser()) {
            return;
        }

        $builder->where($model->qualifyColumn('universidad_id'), $guard->user()->universidad_id);
    }
}
