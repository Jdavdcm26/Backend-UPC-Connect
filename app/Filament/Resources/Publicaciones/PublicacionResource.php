<?php

namespace App\Filament\Resources\Publicaciones;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Enums\Publicaciones\TipoPublicacion;
use App\Filament\Moderacion\AccionesDeModeracion;
use App\Filament\Resources\Publicaciones\Pages\ManagePublicaciones;
use App\Models\Publicaciones\Publicacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Moderación de las publicaciones de la universidad del administrador:
 * ocultar con motivo y volver a mostrar, ambas auditadas.
 */
class PublicacionResource extends Resource
{
    protected static ?string $model = Publicacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Moderación';

    protected static ?string $modelLabel = 'publicación';

    protected static ?string $pluralModelLabel = 'publicaciones';

    protected static ?string $slug = 'publicaciones';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['autor.perfil']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('autor.email')
                    ->label('Autor')
                    ->description(fn (Publicacion $record): ?string => $record->autor->perfil === null
                        ? null
                        : "{$record->autor->perfil->nombres} {$record->autor->perfil->apellidos}")
                    ->searchable(),
                TextColumn::make('contenido')
                    ->limit(80)
                    ->tooltip(fn (Publicacion $record): string => $record->contenido)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('tipo')
                    ->badge(),
                TextColumn::make('categoria.nombre')
                    ->label('Categoría'),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('reacciones_count')
                    ->label('Reacciones')
                    ->counts('reacciones')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comentarios_count')
                    ->label('Comentarios')
                    ->counts('comentarios')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Publicada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoModeracion::class),
                SelectFilter::make('tipo')->options(TipoPublicacion::class),
            ])
            ->recordActions([
                AccionesDeModeracion::ocultar(),
                AccionesDeModeracion::mostrar(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePublicaciones::route('/'),
        ];
    }
}
