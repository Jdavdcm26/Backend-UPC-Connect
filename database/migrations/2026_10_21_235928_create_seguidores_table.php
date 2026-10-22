<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Quién sigue a quién (HU-28).
     */
    public function up(): void
    {
        Schema::create('seguidores', function (Blueprint $table) {
            $table->foreignUuid('seguidor_id')->constrained('users');
            $table->foreignUuid('seguido_id')->index()->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['seguidor_id', 'seguido_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguidores');
    }
};
