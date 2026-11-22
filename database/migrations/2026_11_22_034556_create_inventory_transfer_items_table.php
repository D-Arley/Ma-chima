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
        if (! Schema::hasTable('inventory_transfer_items')) {
            Schema::create('inventory_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_transfer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos');
                $table->unsignedInteger('cantidad');
                $table->timestamps();
            });
        }

        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->unique(['inventory_transfer_id', 'producto_id'], 'transfer_item_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
    }
};
