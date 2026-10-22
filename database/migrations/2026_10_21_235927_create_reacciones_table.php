<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Una reacción por persona y publicación (HU-25).
     */
    public function up(): void
    {
        Schema::create('reacciones', function (Blueprint $table) {
            $table->foreignUuid('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->foreignUuid('usuario_id')->index()->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['publicacion_id', 'usuario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reacciones');
    }
};
