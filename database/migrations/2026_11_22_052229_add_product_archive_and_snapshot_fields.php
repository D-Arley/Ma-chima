<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique(['codigo']);
            $table->string('codigo')->nullable()->change();
        });

        Schema::table('detalle_ventas', function (Blueprint $table): void {
            $table->string('producto_nombre')->nullable()->after('producto_id');
            $table->string('producto_codigo')->nullable()->after('producto_nombre');
            $table->string('producto_talla', 20)->nullable()->after('producto_codigo');
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->string('producto_nombre')->nullable()->after('producto_id');
            $table->string('producto_codigo', 20)->nullable()->after('producto_nombre');
        });

        $detalles = DB::table('detalle_ventas')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->select([
                'detalle_ventas.id',
                'productos.nombre',
                'productos.codigo',
                'productos.talla',
            ])
            ->get();

        foreach ($detalles as $detalle) {
            DB::table('detalle_ventas')
                ->where('id', $detalle->id)
                ->update([
                    'producto_nombre' => $detalle->nombre,
                    'producto_codigo' => $detalle->codigo,
                    'producto_talla' => $detalle->talla,
                ]);
        }

        $movimientos = DB::table('inventory_movements')
            ->join('productos', 'inventory_movements.producto_id', '=', 'productos.id')
            ->select([
                'inventory_movements.id',
                'productos.nombre',
                'productos.codigo',
            ])
            ->get();

        foreach ($movimientos as $movimiento) {
            DB::table('inventory_movements')
                ->where('id', $movimiento->id)
                ->update([
                    'producto_nombre' => $movimiento->nombre,
                    'producto_codigo' => $movimiento->codigo,
                ]);
        }

        Schema::table('productos', function (Blueprint $table): void {
            $table->unique('codigo');
            $table->unique(
                ['categoria_id', 'nombre', 'talla'],
                'productos_categoria_nombre_talla_unique'
            );
        });

        DB::table('productos')->where('activo', true)->update(['codigo' => null]);

        $productos = DB::table('productos')
            ->where('activo', true)
            ->orderBy('id')
            ->get(['id']);

        foreach ($productos as $index => $producto) {
            DB::table('productos')
                ->where('id', $producto->id)
                ->update(['codigo' => str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique('productos_categoria_nombre_talla_unique');
            $table->dropUnique(['codigo']);
        });

        DB::table('productos')
            ->whereNull('codigo')
            ->update(['codigo' => DB::raw("CONCAT('ARCH-', id)")]);

        Schema::table('productos', function (Blueprint $table): void {
            $table->string('codigo')->nullable(false)->change();
            $table->unique('codigo');
        });

        Schema::table('detalle_ventas', function (Blueprint $table): void {
            $table->dropColumn(['producto_nombre', 'producto_codigo', 'producto_talla']);
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropColumn(['producto_nombre', 'producto_codigo']);
        });
    }
};
