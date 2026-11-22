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
        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('comprobante');
            }
            if (! Schema::hasColumn('ventas', 'descuento')) {
                $table->decimal('descuento', 10, 2)->default(0)->after('subtotal');
            }
            if (! Schema::hasColumn('ventas', 'iva')) {
                $table->decimal('iva', 10, 2)->default(0)->after('descuento');
            }
            if (! Schema::hasColumn('ventas', 'ganancia')) {
                $table->decimal('ganancia', 10, 2)->default(0)->after('iva');
            }
            if (! Schema::hasColumn('ventas', 'metodo_pago')) {
                $table->string('metodo_pago', 20)->default('efectivo')->after('ganancia');
            }
            if (! Schema::hasColumn('ventas', 'efectivo_recibido')) {
                $table->decimal('efectivo_recibido', 10, 2)->nullable()->after('metodo_pago');
            }
            if (! Schema::hasColumn('ventas', 'cambio')) {
                $table->decimal('cambio', 10, 2)->default(0)->after('efectivo_recibido');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            //
        });
    }
};
