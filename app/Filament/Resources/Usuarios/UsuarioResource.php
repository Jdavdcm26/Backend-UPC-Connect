<?php

namespace App\Filament\Resources\Usuarios;

use App\Actions\Administracion\ReactivarUsuario;
use App\Actions\Administracion\SuspenderUsuario;
use App\Enums\Autenticacion\EstadoUsuario;
use App\Enums\Autenticacion\Rol;
use App\Filament\Resources\Usuarios\Pages\ManageUsuarios;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Cuentas de la universidad del administrador (HU-50): búsqueda, filtros,
 * suspensión con motivo y reactivación, ambas auditadas.
 */
class UsuarioResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $slug = 'usuarios';

    protected static ?string $recordTitleAttribute = 'email';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->placeholder('Sin nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rol')
                    ->badge(),
                TextColumn::make('estado')
                    ->badge(),
                TextColumn::make('ultimo_acceso_en')
                    ->label('Último acceso')
                    ->dateTime()
                    ->placeholder('Nunca')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rol')->options(Rol::class),
                SelectFilter::make('estado')->options(EstadoUsuario::class),
            ])
            ->recordActions([
                self::accionSuspender(),
                self::accionReactivar(),
            ]);
    }

    private static function accionSuspender(): Action
    {
        return Action::make('suspender')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => auth()->user()->can('suspender', $record))
            ->authorize(fn (User $record): bool => auth()->user()->can('suspender', $record))
            ->modalHeading(fn (User $record): string => "Suspender a {$record->email}")
            ->modalDescription('La cuenta no podrá iniciar sesión y su sesión abierta se cerrará en la siguiente solicitud.')
            ->schema([
                Textarea::make('motivo')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(function (User $record, array $data, SuspenderUsuario $suspenderUsuario): void {
                $suspenderUsuario->handle($record, $data['motivo']);

                Notification::make()->title("{$record->email} quedó suspendido")->success()->send();
            });
    }

    private static function accionReactivar(): Action
    {
        return Action::make('reactivar')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('success')
            ->visible(fn (User $record): bool => auth()->user()->can('reactivar', $record))
            ->authorize(fn (User $record): bool => auth()->user()->can('reactivar', $record))
            ->requiresConfirmation()
            ->modalDescription('La cuenta recupera el acceso. Si nunca verificó su correo, vuelve a quedar pendiente de verificación.')
            ->action(function (User $record, ReactivarUsuario $reactivarUsuario): void {
                $reactivarUsuario->handle($record);

                Notification::make()->title("{$record->email} fue reactivado")->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsuarios::route('/'),
        ];
    }
}
