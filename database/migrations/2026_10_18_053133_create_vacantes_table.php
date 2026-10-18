<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Vacantes, prácticas y pasantías que publican las
     * empresas aprobadas.
     */
    public function up(): void
    {
        Schema::create('vacantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->string('tipo');
            $table->string('titulo', 150);
            $table->text('descripcion');
            $table->text('requisitos');
            $table->string('ciudad', 80);
            $table->string('modalidad');
            $table->string('estado')->default('ACTIVA');
            $table->date('fecha_cierre');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'created_at']);
            $table->index(['empresa_id', 'estado']);
            $table->index('tipo');
            $table->index('modalidad');
            $table->index('ciudad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacantes');
    }
};
