<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Comentarios de las publicaciones (HU-26), con
     * edición, borrado lógico y estado de moderación.
     */
    public function up(): void
    {
        Schema::create('comentarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->foreignUuid('autor_id')->constrained('users');
            $table->text('contenido');
            $table->string('estado');
            $table->timestamp('editado_en')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publicacion_id', 'created_at']);
            $table->index('autor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
