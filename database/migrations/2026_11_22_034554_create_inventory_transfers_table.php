<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 32)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('vendedor_id')->constrained('users');
            $table->string('estado', 20)->default('pendiente');
            $table->string('descripcion', 500)->nullable();
            $table->timestamp('entregado_at');
            $table->timestamp('recibido_at')->nullable();
            $table->foreignId('recibido_por')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['vendedor_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
