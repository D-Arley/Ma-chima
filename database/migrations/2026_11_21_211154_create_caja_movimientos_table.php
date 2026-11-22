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
        if (! Schema::hasTable('caja_movimientos')) {
            Schema::create('caja_movimientos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->nullableMorphs('movable');
                $table->enum('tipo', ['venta_efectivo', 'venta_transferencia', 'transferencia_banco', 'apertura', 'ajuste']);
                $table->decimal('amount', 12, 2);
                $table->decimal('cash_after', 12, 2)->default(0);
                $table->decimal('bank_after', 12, 2)->default(0);
                $table->string('descripcion');
                $table->timestamp('fecha');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
