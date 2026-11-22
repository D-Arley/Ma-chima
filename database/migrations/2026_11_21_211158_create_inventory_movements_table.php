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
        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('producto_id')->constrained('productos');
                $table->enum('tipo', [
                    'entrada',
                    'venta',
                    'ajuste',
                    'correccion',
                    'danio',
                    'perdida',
                    'garantia',
                    'entrega_vendedor',
                    'recepcion_vendedor',
                ]);
                $table->integer('cantidad');
                $table->integer('stock_before');
                $table->integer('stock_after');
                $table->decimal('costo_unitario', 10, 2)->default(0);
                $table->string('motivo', 100)->nullable();
                $table->string('descripcion', 1000)->nullable();
                $table->string('referencia')->nullable();
                $table->timestamps();
                $table->index(['tipo', 'created_at']);
                $table->index(['producto_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
