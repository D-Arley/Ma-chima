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
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedInteger('stock_sistema');
            $table->unsignedInteger('cantidad_contada');
            $table->integer('diferencia');
            $table->unsignedBigInteger('ultimo_movimiento_id')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->timestamp('revisado_at')->nullable();
            $table->string('nota', 1000)->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};
