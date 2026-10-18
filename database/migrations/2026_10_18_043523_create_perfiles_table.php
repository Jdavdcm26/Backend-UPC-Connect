<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Perfil profesional de estudiantes y egresados.
     */
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('usuario_id')->unique()->constrained('users');
            $table->string('nombres', 80);
            $table->string('apellidos', 80);
            $table->string('slug')->unique();
            $table->string('titular', 120)->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignUuid('carrera_id')->index()->constrained('carreras');
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->unsignedSmallInteger('anio_grado')->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->json('intereses')->nullable();
            // Visibilidad por sección; null = matriz por defecto (la configuración llega con HU-16).
            $table->json('visibilidad')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
