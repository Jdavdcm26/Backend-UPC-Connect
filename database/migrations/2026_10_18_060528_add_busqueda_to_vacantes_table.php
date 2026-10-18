<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Texto y ciudad normalizados (minúsculas, sin tildes)
     * para buscar y filtrar vacantes igual en PostgreSQL y SQLite (HU-43). El
     * modelo los mantiene al guardar.
     */
    public function up(): void
    {
        Schema::table('vacantes', function (Blueprint $table) {
            $table->text('texto_busqueda')->nullable();
            $table->string('ciudad_normalizada', 80)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacantes', function (Blueprint $table) {
            $table->dropIndex(['ciudad_normalizada']);
            $table->dropColumn(['texto_busqueda', 'ciudad_normalizada']);
        });
    }
};
