<?php

namespace App\Filament\Resources\Carreras\Pages;

use App\Filament\Resources\Carreras\CarreraResource;
use App\Models\Catalogos\Carrera;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCarreras extends ManageRecords
{
    protected static string $resource = CarreraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data): Carrera => auth()->user()->universidad->carreras()->create($data)),
        ];
    }
}
