<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Habilidades de cada perfil, sin duplicados. Se crea
     * junto con perfiles para que la fusión de habilidades (BE-15) se pueda
     * probar antes de HU-11.
     */
    public function up(): void
    {
        Schema::create('perfil_habilidad', function (Blueprint $table) {
            $table->foreignUuid('perfil_id')->constrained('perfiles')->cascadeOnDelete();
            $table->foreignUuid('habilidad_id')->index()->constrained('habilidades');
            $table->string('nivel')->nullable();
            $table->timestamps();

            $table->primary(['perfil_id', 'habilidad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfil_habilidad');
    }
};
