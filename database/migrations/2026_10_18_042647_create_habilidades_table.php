<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Catálogo de competencias que las personas agregan a
     * su perfil y por el que las empresas buscan candidatos.
     */
    public function up(): void
    {
        Schema::create('habilidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre')->unique();
            $table->string('tipo');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['activa', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habilidades');
    }
};
