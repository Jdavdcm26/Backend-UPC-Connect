<?php

namespace App\Filament\Resources\Habilidades\Pages;

use App\Filament\Resources\Habilidades\HabilidadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHabilidades extends ManageRecords
{
    protected static string $resource = HabilidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
