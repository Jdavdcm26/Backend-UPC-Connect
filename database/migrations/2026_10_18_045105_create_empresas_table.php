<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Organizaciones que publican vacantes; cada una es la
     * cuenta de un usuario con rol EMPRESA.
     */
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('usuario_id')->unique()->constrained('users');
            $table->string('razon_social');
            $table->string('nit', 20)->unique();
            $table->string('sector', 100);
            $table->text('descripcion')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('ciudad', 80);
            $table->string('estado_aprobacion')->default('PENDIENTE')->index();
            $table->foreignUuid('aprobada_por')->nullable()->constrained('users');
            $table->timestamp('aprobada_en')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
