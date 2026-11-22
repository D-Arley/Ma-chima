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
        Schema::create('inventory_count_run_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_count_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('producto_nombre');
            $table->string('producto_codigo', 20);
            $table->unsignedInteger('stock_sistema');
            $table->decimal('costo_unitario', 10, 2);
            $table->unsignedBigInteger('ultimo_movimiento_id')->nullable();
            $table->unsignedInteger('cantidad_contada')->nullable();
            $table->integer('diferencia')->nullable();
            $table->unique(['inventory_count_run_id', 'producto_id'], 'count_run_producto_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_run_items');
    }
};
