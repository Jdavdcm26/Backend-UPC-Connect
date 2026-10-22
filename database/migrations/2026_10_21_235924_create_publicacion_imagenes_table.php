<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Hasta cuatro imágenes por publicación (HU-21); el
     * archivo vive en el disco público y aquí se guarda su ruta.
     */
    public function up(): void
    {
        Schema::create('publicacion_imagenes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->string('ruta');
            $table->timestamps();

            $table->index(['publicacion_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publicacion_imagenes');
    }
};
