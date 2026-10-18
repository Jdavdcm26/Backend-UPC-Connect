<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Tokens de las invitaciones para definir la
     * contraseña de las cuentas que crea el administrador. Van en su propia
     * tabla porque duran días, no minutos como los de recuperación.
     */
    public function up(): void
    {
        Schema::create('invitaciones_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitaciones_tokens');
    }
};
