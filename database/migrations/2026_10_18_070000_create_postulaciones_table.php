<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Postulaciones de estudiantes y egresados a vacantes;
     * una sola por persona y vacante.
     */
    public function up(): void
    {
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vacante_id')->constrained('vacantes');
            $table->foreignUuid('usuario_id')->constrained('users');
            $table->text('mensaje')->nullable();
            $table->string('estado');
            $table->timestamps();

            $table->unique(['vacante_id', 'usuario_id']);
            $table->index(['usuario_id', 'created_at']);
            $table->index(['vacante_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulaciones');
    }
};
