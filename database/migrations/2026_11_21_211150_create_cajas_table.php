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
        if (! Schema::hasTable('cajas')) {
            Schema::create('cajas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->date('fecha');
                $table->decimal('opening_cash', 12, 2)->default(200000);
                $table->decimal('cash_balance', 12, 2)->default(200000);
                $table->decimal('transfer_balance', 12, 2)->default(0);
                $table->decimal('bank_balance', 12, 2)->default(0);
                $table->enum('status', ['abierta', 'cerrada'])->default('abierta');
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'fecha']);
            });
        }

        if (! Schema::hasColumn('ventas', 'caja_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('caja_id')->nullable()->after('user_id')->constrained('cajas');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caja_id');
        });
        Schema::dropIfExists('cajas');
    }
};
