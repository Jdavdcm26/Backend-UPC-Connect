<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuarioResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsuarios extends ManageRecords
{
    protected static string $resource = UsuarioResource::class;
}
