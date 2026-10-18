<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Actions\Administracion\DarDeAltaEgresado;
use App\Actions\Administracion\DarDeAltaEmpresa;
use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use App\Rules\Vacantes\NitValido;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageUsuarios extends ManageRecords
{
    protected static string $resource = UsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->accionAltaEmpresa(),
            $this->accionAltaEgresado(),
        ];
    }

    /**
     * Alta de una empresa ya aprobada, con invitación a su correo (HU-50).
     */
    private function accionAltaEmpresa(): Action
    {
        return Action::make('altaEmpresa')
            ->label('Dar de alta empresa')
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->authorize(fn (): bool => auth()->user()->can('create', Empresa::class))
            ->modalDescription('La empresa queda aprobada y recibe un correo para definir su contraseña.')
            ->schema([
                TextInput::make('razon_social')->label('Razón social')->required()->maxLength(255),
                TextInput::make('nit')
                    ->label('NIT')
                    ->helperText('Con el dígito de verificación, por ejemplo 900123456-8.')
                    ->required()
                    ->rules([
                        new NitValido,
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (Empresa::query()->withTrashed()->where('nit', Empresa::normalizarNit((string) $value))->exists()) {
                                $fail('Ya existe una empresa con este NIT. Si es la misma, búscala en la lista de usuarios.');
                            }
                        },
                    ]),
                TextInput::make('sector')->required()->maxLength(100),
                TextInput::make('ciudad')->required()->maxLength(80)->default('Valledupar'),
                TextInput::make('sitio_web')->label('Sitio web')->url()->maxLength(255),
                Textarea::make('descripcion')->label('Descripción')->maxLength(1000),
                TextInput::make('nombre_contacto')->label('Nombre del contacto')->required()->maxLength(255),
                $this->campoCorreo('Correo de la cuenta de la empresa'),
            ])
            ->action(function (array $data, DarDeAltaEmpresa $darDeAltaEmpresa): void {
                $empresa = $darDeAltaEmpresa->handle(auth()->user(), $data);

                Notification::make()
                    ->title("{$empresa->razon_social} quedó dada de alta")
                    ->body("Se envió la invitación a {$data['email']}.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Alta de un egresado con invitación a su correo (HU-50).
     */
    private function accionAltaEgresado(): Action
    {
        return Action::make('altaEgresado')
            ->label('Dar de alta egresado')
            ->icon(Heroicon::OutlinedAcademicCap)
            ->color('gray')
            ->authorize(fn (): bool => auth()->user()->can('create', User::class))
            ->modalDescription('La cuenta queda activa y la persona recibe un correo para definir su contraseña.')
            ->schema([
                TextInput::make('nombre')->required()->maxLength(255),
                $this->campoCorreo('Correo del egresado'),
            ])
            ->action(function (array $data, DarDeAltaEgresado $darDeAltaEgresado): void {
                $darDeAltaEgresado->handle(auth()->user(), $data['nombre'], $data['email']);

                Notification::make()
                    ->title('Egresado dado de alta')
                    ->body("Se envió la invitación a {$data['email']}.")
                    ->success()
                    ->send();
            });
    }

    private function campoCorreo(string $etiqueta): TextInput
    {
        return TextInput::make('email')
            ->label($etiqueta)
            ->email()
            ->required()
            ->maxLength(255)
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (User::query()->withoutGlobalScopes()->where('email', mb_strtolower(trim((string) $value)))->exists()) {
                        $fail('Ya existe una cuenta con este correo.');
                    }
                },
            ]);
    }
}
