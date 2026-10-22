<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Publicaciones del feed (HU-18 a HU-24). Llevan la
     * universidad del autor para que el alcance por universidad filtre sin
     * unir con users; los detalles guardan los campos de las plantillas de
     * servicio y de búsqueda de equipo.
     */
    public function up(): void
    {
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('universidad_id')->constrained('universidades');
            $table->foreignUuid('autor_id')->constrained('users');
            $table->foreignUuid('categoria_id')->constrained('categorias');
            $table->string('tipo');
            $table->text('contenido');
            $table->json('detalles')->nullable();
            $table->boolean('abierta')->default(true);
            $table->string('estado');
            $table->timestamp('editada_en')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['universidad_id', 'estado', 'created_at']);
            $table->index(['autor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};
