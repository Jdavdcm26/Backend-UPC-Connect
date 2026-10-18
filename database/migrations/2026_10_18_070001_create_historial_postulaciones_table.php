<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Cada cambio de estado de una postulación, con quién
     * lo hizo y cuándo (HU-47, RNF-19). Las filas no se editan.
     */
    public function up(): void
    {
        Schema::create('historial_postulaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('postulacion_id')->constrained('postulaciones');
            $table->string('estado');
            $table->foreignUuid('cambiado_por')->constrained('users');
            $table->timestamp('cambiado_en');

            $table->index(['postulacion_id', 'cambiado_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_postulaciones');
    }
};
