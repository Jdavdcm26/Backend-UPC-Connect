<?php

namespace App\Filament\Resources\Categorias;

use App\Filament\Resources\Categorias\Pages\ManageCategorias;
use App\Models\Catalogos\Categoria;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Categorías de publicación (HU-54); se usan desde el Release 2.
 */
class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Toggle::make('activa')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('activa')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('activa'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategorias::route('/'),
        ];
    }
}
