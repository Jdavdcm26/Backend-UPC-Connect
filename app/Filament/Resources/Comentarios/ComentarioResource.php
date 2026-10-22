<?php

namespace App\Filament\Resources\Comentarios;

use App\Enums\Publicaciones\EstadoModeracion;
use App\Filament\Moderacion\AccionesDeModeracion;
use App\Filament\Resources\Comentarios\Pages\ManageComentarios;
use App\Models\Publicaciones\Comentario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Moderación de los comentarios de las publicaciones de la universidad del
 * administrador: ocultar con motivo y volver a mostrar, ambas auditadas.
 */
class ComentarioResource extends Resource
{
    protected static ?string $model = Comentario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Moderación';

    protected static ?string $modelLabel = 'comentario';

    protected static ?string $pluralModelLabel = 'comentarios';

    protected static ?string $slug = 'comentarios';

    /**
     * Solo los comentarios de publicaciones de la universidad del
     * administrador (las publicaciones ya filtran por universidad).
     *
     * @return Builder<Comentario>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('publicacion');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['autor.perfil', 'publicacion']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('autor.email')
                    ->label('Autor')
                    ->description(fn (Comentario $record): ?string => $record->autor->perfil === null
                        ? null
                        : "{$record->autor->perfil->nombres} {$record->autor->perfil->apellidos}")
                    ->searchable(),
                TextColumn::make('contenido')
                    ->limit(80)
                    ->tooltip(fn (Comentario $record): string => $record->contenido)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('publicacion.contenido')
                    ->label('Publicación')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Comentado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoModeracion::class),
            ])
            ->recordActions([
                AccionesDeModeracion::ocultar(),
                AccionesDeModeracion::mostrar(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageComentarios::route('/'),
        ];
    }
}
