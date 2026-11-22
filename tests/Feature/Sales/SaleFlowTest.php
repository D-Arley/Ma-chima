<?php

namespace Tests\Feature\Sales;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_sale_updates_stock_profit_and_cash_atomically(): void
    {
        $seller = User::factory()->create(['role' => 'vendedor']);
        $category = Categoria::create(['nombre' => 'Zapatos']);
        $product = Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'ZAP-TEST',
            'nombre' => 'Zapato prueba',
            'descripcion' => 'Producto de prueba',
            'talla' => '40',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 8,
            'activo' => true,
        ]);
        $caja = Caja::create([
            'user_id' => $seller->id,
            'fecha' => today(),
            'opening_cash' => 200000,
            'cash_balance' => 200000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'abierta',
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($seller)->post('/ventas', [
            'metodo_pago' => 'efectivo',
            'descuento' => 0,
            'efectivo_recibido' => 250000,
            'items' => json_encode([
                ['producto_id' => $product->id, 'cantidad' => 2],
            ]),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('productos', [
            'id' => $product->id,
            'stock' => 6,
        ]);
        $this->assertDatabaseHas('detalle_ventas', [
            'producto_id' => $product->id,
            'cantidad' => 2,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'producto_id' => $product->id,
            'producto_nombre' => 'Zapato prueba',
            'producto_codigo' => 'ZAP-TEST',
            'tipo' => 'venta',
            'cantidad' => -2,
        ]);
        $this->assertDatabaseHas('ventas', [
            'user_id' => $seller->id,
            'caja_id' => $caja->id,
            'metodo_pago' => 'efectivo',
        ]);

        $sale = Venta::query()->where('user_id', $seller->id)->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('inventario.update', $product), [
                'categoria_id' => $category->id,
                'codigo' => 'CODIGO-ALTERADO',
                'nombre' => 'Zapato con nombre nuevo',
                'talla' => '41',
                'precio' => 119000,
                'costo' => 50000,
                'ajuste' => 0,
            ])
            ->assertRedirect(route('inventario.index'));
        $product->refresh();
        $this->assertSame('ZAP-TEST', $product->codigo);

        $this->actingAs($seller)
            ->get(route('ventas.show', $sale))
            ->assertOk()
            ->assertSee('Comprobante de venta')
            ->assertSee($product->codigo)
            ->assertSee('Zapato prueba')
            ->assertSee('IVA incluido (19%)')
            ->assertSee('Efectivo recibido')
            ->assertSee('No es factura electrónica');
    }

    public function test_sale_is_rejected_when_stock_is_insufficient(): void
    {
        $seller = User::factory()->create(['role' => 'vendedor']);
        $category = Categoria::create(['nombre' => 'Camisas']);
        $product = Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'CMS-TEST',
            'nombre' => 'Camisa prueba',
            'talla' => 'M',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 1,
            'activo' => true,
        ]);
        Caja::create([
            'user_id' => $seller->id,
            'fecha' => today(),
            'opening_cash' => 200000,
            'cash_balance' => 200000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'abierta',
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($seller)->post('/ventas', [
            'metodo_pago' => 'efectivo',
            'efectivo_recibido' => 500000,
            'items' => json_encode([
                ['producto_id' => $product->id, 'cantidad' => 2],
            ]),
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseHas('productos', ['id' => $product->id, 'stock' => 1]);
    }

    public function test_sales_page_renders_product_click_data_that_alpine_can_parse(): void
    {
        $seller = User::factory()->create(['role' => 'vendedor']);
        $category = Categoria::create(['nombre' => 'Camisas']);
        $product = Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'CMS-POS-TEST',
            'nombre' => 'Camisa para carrito',
            'talla' => 'M',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 2,
            'activo' => true,
        ]);
        Caja::create([
            'user_id' => $seller->id,
            'fecha' => today(),
            'opening_cash' => 200000,
            'cash_balance' => 200000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'abierta',
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($seller)->get('/ventas');

        $response->assertOk()
            ->assertSee('data-producto-id="'.$product->id.'"', false)
            ->assertSee('dataset.productoNombre', false)
            ->assertSee('Disponibles en bodega: 2')
            ->assertSee("x-text=\"'Disponibles en bodega: ' + item.stock\"", false)
            ->assertSee('max-h-64 space-y-3 overflow-y-auto overscroll-contain', false)
            ->assertDontSee('xl:max-h-[calc(100vh', false)
            ->assertDontSee('add(JSON.parse(', false);

        $this->assertSame(1, substr_count($response->getContent(), 'overflow-y-auto'));
    }

    public function test_duplicate_cart_lines_cannot_oversell_a_product(): void
    {
        $seller = User::factory()->create(['role' => 'vendedor']);
        $category = Categoria::create(['nombre' => 'Camisas']);
        $product = Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'CMS-DUPLICATE-TEST',
            'nombre' => 'Camisa con stock limitado',
            'talla' => 'M',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 3,
            'activo' => true,
        ]);
        Caja::create([
            'user_id' => $seller->id,
            'fecha' => today(),
            'opening_cash' => 200000,
            'cash_balance' => 200000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'abierta',
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($seller)->post('/ventas', [
            'metodo_pago' => 'efectivo',
            'efectivo_recibido' => 500000,
            'items' => json_encode([
                ['producto_id' => $product->id, 'cantidad' => 2],
                ['producto_id' => $product->id, 'cantidad' => 2],
            ]),
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseHas('productos', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_cash_transfer_always_preserves_the_200000_minimum(): void
    {
        $seller = User::factory()->create(['role' => 'vendedor']);
        Caja::create([
            'user_id' => $seller->id,
            'fecha' => today(),
            'opening_cash' => 200000,
            'cash_balance' => 1200000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'abierta',
            'opened_at' => now(),
        ]);

        $this->actingAs($seller)->post('/caja/transferir', [
            'amount' => 1000001,
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('cajas', [
            'user_id' => $seller->id,
            'cash_balance' => 1200000,
            'bank_balance' => 0,
        ]);
    }
}
