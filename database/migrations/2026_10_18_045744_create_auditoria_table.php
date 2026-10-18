<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Registro de las acciones administrativas (RF-58,
     * RNF-17). Un trigger impide modificar o borrar filas, para que el
     * registro sea confiable aunque se escriba SQL a mano.
     */
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->constrained('users');
            $table->string('accion', 100);
            $table->string('entidad', 100);
            $table->string('entidad_id')->nullable();
            $table->json('detalle')->nullable();
            $table->timestamp('created_at');

            $table->index(['actor_id', 'created_at']);
            $table->index(['entidad', 'entidad_id']);
        });

        match (DB::getDriverName()) {
            'pgsql' => DB::unprepared(<<<'SQL'
                CREATE FUNCTION auditoria_inmutable() RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'La auditoría no se puede modificar ni borrar.';
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER auditoria_sin_cambios
                    BEFORE UPDATE OR DELETE ON auditoria
                    FOR EACH ROW EXECUTE FUNCTION auditoria_inmutable();
                SQL),
            'sqlite' => DB::unprepared(<<<'SQL'
                CREATE TRIGGER auditoria_sin_actualizar BEFORE UPDATE ON auditoria
                BEGIN SELECT RAISE(ABORT, 'La auditoría no se puede modificar ni borrar.'); END;

                CREATE TRIGGER auditoria_sin_borrar BEFORE DELETE ON auditoria
                BEGIN SELECT RAISE(ABORT, 'La auditoría no se puede modificar ni borrar.'); END;
                SQL),
            default => null,
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS auditoria_inmutable()');
        }
    }
};
