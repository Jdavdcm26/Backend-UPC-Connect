<?php

namespace App\Filament\Moderacion;

use App\Actions\Publicaciones\ModerarContenido;
use App\Enums\Publicaciones\EstadoModeracion;
use App\Models\Publicaciones\Comentario;
use App\Models\Publicaciones\Publicacion;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Acciones de ocultar y volver a mostrar que comparten las tablas de
 * publicaciones y comentarios. Ambas quedan en la auditoría.
 */
final class AccionesDeModeracion
{
    public static function ocultar(): Action
    {
        return Action::make('ocultar')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('danger')
            ->visible(fn (Publicacion|Comentario $record): bool => $record->estado === EstadoModeracion::Visible)
            ->authorize(fn (Publicacion|Comentario $record): bool => auth()->user()->can('moderar', $record))
            ->modalDescription('Deja de mostrarse en la plataforma, pero se conserva para revisarlo. Puedes volver a mostrarlo después.')
            ->schema([
                Textarea::make('motivo')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(function (Publicacion|Comentario $record, array $data, ModerarContenido $moderarContenido): void {
                $moderarContenido->handle($record, EstadoModeracion::Oculto, $data['motivo']);

                Notification::make()->title('Contenido ocultado')->success()->send();
            });
    }

    public static function mostrar(): Action
    {
        return Action::make('mostrar')
            ->icon(Heroicon::OutlinedEye)
            ->color('success')
            ->visible(fn (Publicacion|Comentario $record): bool => $record->estado === EstadoModeracion::Oculto)
            ->authorize(fn (Publicacion|Comentario $record): bool => auth()->user()->can('moderar', $record))
            ->requiresConfirmation()
            ->modalDescription('Vuelve a mostrarse en la plataforma.')
            ->action(function (Publicacion|Comentario $record, ModerarContenido $moderarContenido): void {
                $moderarContenido->handle($record, EstadoModeracion::Visible);

                Notification::make()->title('Contenido visible de nuevo')->success()->send();
            });
    }
}
