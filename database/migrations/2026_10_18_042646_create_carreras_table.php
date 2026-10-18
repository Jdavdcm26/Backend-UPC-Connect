<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Programas académicos oficiales de cada universidad.
     * Una carrera desactivada no se ofrece en altas nuevas, pero se conserva en
     * los perfiles que ya la tienen.
     */
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('universidad_id')->constrained('universidades');
            $table->string('nombre');
            $table->string('facultad');
            $table->string('nivel');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['universidad_id', 'nombre']);
            $table->index(['universidad_id', 'activa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
