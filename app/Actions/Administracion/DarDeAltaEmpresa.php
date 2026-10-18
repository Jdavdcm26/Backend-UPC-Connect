<?php

namespace App\Actions\Administracion;

use App\Enums\Autenticacion\Rol;
use App\Enums\Vacantes\EstadoAprobacionEmpresa;
use App\Models\Administracion\Auditoria;
use App\Models\User;
use App\Models\Vacantes\Empresa;
use Illuminate\Support\Facades\DB;

class DarDeAltaEmpresa
{
    public function __construct(
        private CrearCuentaInvitada $crearCuentaInvitada,
        private EnviarInvitacion $enviarInvitacion,
    ) {}

    /**
     * Crea la cuenta de la empresa y su registro ya aprobado (hallazgo 5), y
     * envía la invitación para definir la contraseña (HU-50, criterio 4).
     *
     * @param  array{nombre_contacto: string, email: string, razon_social: string, nit: string, sector: string, ciudad: string, sitio_web?: ?string, descripcion?: ?string}  $datos
     */
    public function handle(User $administrador, array $datos): Empresa
    {
        $empresa = DB::transaction(function () use ($administrador, $datos): Empresa {
            $usuario = $this->crearCuentaInvitada->handle($administrador, $datos['nombre_contacto'], $datos['email'], Rol::Empresa);

            $empresa = new Empresa([
                'razon_social' => $datos['razon_social'],
                'nit' => Empresa::normalizarNit($datos['nit']),
                'sector' => $datos['sector'],
                'ciudad' => $datos['ciudad'],
                'sitio_web' => $datos['sitio_web'] ?? null,
                'descripcion' => $datos['descripcion'] ?? null,
            ]);
            $empresa->forceFill([
                'usuario_id' => $usuario->id,
                'estado_aprobacion' => EstadoAprobacionEmpresa::Aprobada,
                'aprobada_por' => $administrador->id,
                'aprobada_en' => now(),
            ])->save();

            Auditoria::registrar('empresa.creada', $empresa, [
                'razon_social' => $empresa->razon_social,
                'nit' => $empresa->nit,
                'email' => $usuario->email,
            ]);

            return $empresa;
        });

        $this->enviarInvitacion->handle($empresa->usuario);

        return $empresa;
    }
}
