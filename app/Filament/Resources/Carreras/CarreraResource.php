<?php

namespace App\Filament\Resources\Carreras;

use App\Enums\Catalogos\NivelCarrera;
use App\Filament\Resources\Carreras\Pages\ManageCarreras;
use App\Models\Catalogos\Carrera;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

/**
 * Programas académicos de la universidad del administrador (HU-53). Una
 * carrera no se borra: se desactiva, para conservarla en los perfiles.
 */
class CarreraResource extends Resource
{
    protected static ?string $model = Carrera::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'carrera';

    protected static ?string $pluralModelLabel = 'carreras';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('universidad_id', auth()->user()->universidad_id)),
                TextInput::make('facultad')
                    ->required()
                    ->maxLength(255),
                Select::make('nivel')
                    ->options(NivelCarrera::class)
                    ->default(NivelCarrera::Pregrado)
                    ->required(),
                Toggle::make('activa')
                    ->helperText('Una carrera inactiva no se ofrece en altas nuevas, pero se conserva en los perfiles que ya la tienen.')
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
                TextColumn::make('facultad')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nivel')
                    ->badge(),
                IconColumn::make('activa')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageCarreras::route('/'),
        ];
    }
}
