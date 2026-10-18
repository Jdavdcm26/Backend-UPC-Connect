<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Carreras objetivo de cada vacante.
     */
    public function up(): void
    {
        Schema::create('vacante_carrera', function (Blueprint $table) {
            $table->foreignUuid('vacante_id')->constrained('vacantes')->cascadeOnDelete();
            $table->foreignUuid('carrera_id')->constrained('carreras');

            $table->primary(['vacante_id', 'carrera_id']);
            $table->index(['carrera_id', 'vacante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacante_carrera');
    }
};
