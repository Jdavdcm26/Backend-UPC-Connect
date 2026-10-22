<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Bloqueos entre usuarios (HU-31).
     */
    public function up(): void
    {
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->foreignUuid('bloqueador_id')->constrained('users');
            $table->foreignUuid('bloqueado_id')->index()->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['bloqueador_id', 'bloqueado_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloqueos');
    }
};
