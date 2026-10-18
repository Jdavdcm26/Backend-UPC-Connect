<?php

namespace App\Filament\Resources\Habilidades;

use App\Actions\Catalogos\FusionarHabilidades;
use App\Enums\Catalogos\TipoHabilidad;
use App\Filament\RegistroDeAuditoria;
use App\Filament\Resources\Habilidades\Pages\ManageHabilidades;
use App\Models\Administracion\Auditoria;
use App\Models\Catalogos\Habilidad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Catálogo de habilidades (HU-54), con la fusión de duplicados.
 */
class HabilidadResource extends Resource
{
    protected static ?string $model = Habilidad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'habilidad';

    protected static ?string $pluralModelLabel = 'habilidades';

    protected static ?string $slug = 'habilidades';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Select::make('tipo')
                    ->options(TipoHabilidad::class)
                    ->required(),
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
                TextColumn::make('tipo')
                    ->badge(),
                TextColumn::make('perfiles_count')
                    ->label('Perfiles')
                    ->counts('perfiles')
                    ->sortable(),
                IconColumn::make('activa')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('tipo')->options(TipoHabilidad::class),
                TernaryFilter::make('activa'),
            ])
            ->recordActions([
                EditAction::make()->after(RegistroDeAuditoria::alEditar('habilidad.actualizada')),
                self::accionFusionar(),
            ]);
    }

    /**
     * Fusiona una habilidad duplicada en otra: sus perfiles pasan a la
     * habilidad elegida sin duplicados y la duplicada queda inactiva.
     */
    private static function accionFusionar(): Action
    {
        return Action::make('fusionar')
            ->label('Fusionar')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('warning')
            ->visible(fn (Habilidad $record): bool => $record->activa)
            ->authorize(fn (Habilidad $record): bool => auth()->user()->can('update', $record))
            ->modalHeading(fn (Habilidad $record): string => "Fusionar «{$record->nombre}»")
            ->modalDescription('Los perfiles que tienen esta habilidad pasarán a la habilidad elegida, sin duplicados, y esta quedará inactiva.')
            ->schema([
                Select::make('destino_id')
                    ->label('Fusionar en')
                    ->options(fn (Habilidad $record): array => Habilidad::query()
                        ->activas()
                        ->whereKeyNot($record->getKey())
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (Habilidad $record, array $data, FusionarHabilidades $fusionarHabilidades): void {
                $destino = Habilidad::query()->activas()->whereKeyNot($record->getKey())->findOrFail($data['destino_id']);

                $perfiles = $fusionarHabilidades->handle($record, $destino);

                Auditoria::registrar('habilidad.fusionada', $record, [
                    'origen' => $record->nombre,
                    'destino_id' => $destino->id,
                    'destino' => $destino->nombre,
                    'perfiles' => $perfiles,
                ]);

                Notification::make()
                    ->title("«{$record->nombre}» se fusionó en «{$destino->nombre}»")
                    ->body("Perfiles actualizados: {$perfiles}.")
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHabilidades::route('/'),
        ];
    }
}
