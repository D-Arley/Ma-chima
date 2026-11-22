<?php

namespace Tests\Feature\Movements;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\Categoria;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\DemoSalesHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSalesHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_two_months_of_reproducible_demo_sales_without_changing_current_stock(): void
    {
        $this->travelTo('2026-11-22 12:00:00');
        $seller = User::factory()->create(['role' => 'vendedor']);
        $category = Categoria::create(['nombre' => 'Camisas']);
        $product = Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'CMS-DEMO-HISTORY',
            'nombre' => 'Camisa demo',
            'talla' => 'M',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 100,
            'activo' => true,
        ]);

        $this->seed(DemoSalesHistorySeeder::class);
        $this->seed(DemoSalesHistorySeeder::class);

        $this->assertDatabaseCount('ventas', 62);
        $this->assertDatabaseCount('caja_movimientos', 124);
        $this->assertDatabaseHas('ventas', [
            'comprobante' => 'DEMO-20260922',
            'user_id' => $seller->id,
        ]);
        $this->assertDatabaseHas('ventas', [
            'comprobante' => 'DEMO-20261122',
            'user_id' => $seller->id,
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $product->id,
            'stock' => 100,
        ]);
        $this->assertDatabaseCount('inventory_movements', 63);
        $this->assertSame(62, Venta::query()->where('user_id', $seller->id)->count());
        $this->assertSame(62, CajaMovimiento::query()->where('user_id', $seller->id)->where('tipo', 'like', 'venta_%')->count());
        $this->assertSame(63, InventoryMovement::query()->where('producto_id', $product->id)->count());
    }

    public function test_it_leaves_an_existing_cashbox_untouched_when_creating_demo_history(): void
    {
        $this->travelTo('2026-11-22 12:00:00');
        $seller = User::factory()->create(['role' => 'vendedor']);
        $category = Categoria::create(['nombre' => 'Camisas']);
        Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'CMS-DEMO-CASH',
            'nombre' => 'Camisa demo caja',
            'talla' => 'M',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 100,
            'activo' => true,
        ]);
        $caja = Caja::create([
            'user_id' => $seller->id,
            'fecha' => today(),
            'opening_cash' => 200000,
            'cash_balance' => 275000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'abierta',
            'opened_at' => now()->setTime(8, 0),
        ]);

        $this->seed(DemoSalesHistorySeeder::class);

        $caja->refresh();
        $this->assertSame('275000.00', $caja->cash_balance);
        $this->assertDatabaseMissing('ventas', ['comprobante' => 'DEMO-20261122']);
        $this->assertSame(61, Venta::query()->count());
    }
}
