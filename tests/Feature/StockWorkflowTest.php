<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\InventoryCountRun;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_bodeguero_dispatches_stock_and_seller_confirms_receipt(): void
    {
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $vendedor = User::factory()->create(['role' => 'vendedor']);
        $producto = $this->createProduct();

        $this->actingAs($bodeguero)
            ->post(route('logistica.deliver'), [
                'vendedor_id' => $vendedor->id,
                'items' => [
                    ['producto_id' => $producto->id, 'cantidad' => 3],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $transferencia = InventoryTransfer::firstOrFail();
        $this->assertSame('pendiente', $transferencia->estado);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 5]);
        $this->assertDatabaseHas('inventory_movements', [
            'producto_id' => $producto->id,
            'tipo' => 'entrega_vendedor',
            'cantidad' => -3,
            'referencia' => $transferencia->codigo,
        ]);

        $this->actingAs($vendedor)
            ->get(route('logistica.index'))
            ->assertOk()
            ->assertSee($transferencia->codigo)
            ->assertSee('Confirmar recepción');

        $this->patch(route('logistica.receive', $transferencia))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_transfers', [
            'id' => $transferencia->id,
            'estado' => 'recibida',
            'recibido_por' => $vendedor->id,
        ]);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 8]);
        $this->assertDatabaseHas('inventory_movements', [
            'producto_id' => $producto->id,
            'tipo' => 'recepcion_vendedor',
            'cantidad' => 3,
            'referencia' => $transferencia->codigo,
        ]);
    }

    public function test_seller_cannot_dispatch_stock_or_receive_another_sellers_transfer(): void
    {
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $vendedor = User::factory()->create(['role' => 'vendedor']);
        $otroVendedor = User::factory()->create(['role' => 'vendedor']);
        $producto = $this->createProduct();

        $this->actingAs($vendedor)
            ->post(route('logistica.deliver'), [
                'vendedor_id' => $otroVendedor->id,
                'items' => [
                    ['producto_id' => $producto->id, 'cantidad' => 2],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('inventory_transfers', 0);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 8]);

        $this->actingAs($bodeguero)->post(route('logistica.deliver'), [
            'vendedor_id' => $vendedor->id,
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 2],
            ],
        ]);
        $transferencia = InventoryTransfer::firstOrFail();

        $this->actingAs($otroVendedor)
            ->patch(route('logistica.receive', $transferencia))
            ->assertForbidden();

        $this->assertDatabaseHas('inventory_transfers', [
            'id' => $transferencia->id,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 6]);
    }

    public function test_dispatch_rolls_back_when_requested_quantity_exceeds_available_stock(): void
    {
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $vendedor = User::factory()->create(['role' => 'vendedor']);
        $producto = $this->createProduct();

        $this->actingAs($bodeguero)
            ->post(route('logistica.deliver'), [
                'vendedor_id' => $vendedor->id,
                'items' => [
                    ['producto_id' => $producto->id, 'cantidad' => 9],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('inventory_transfers', 0);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 8]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_seller_count_waits_for_bodeguero_approval_before_adjusting_stock(): void
    {
        $vendedor = User::factory()->create(['role' => 'vendedor']);
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $producto = $this->createProduct();

        $this->actingAs($vendedor)
            ->post(route('logistica.count-runs.start'))
            ->assertRedirect();

        $conteo = InventoryCountRun::firstOrFail();
        $this->get(route('logistica.count-runs.show', $conteo))
            ->assertOk()
            ->assertSee('Cantidad encontrada')
            ->assertDontSee('Sistema');

        $this->put(route('logistica.count-runs.finish', $conteo), [
            'items' => [$producto->id => 6],
            'nota' => 'Conteo de prueba',
        ])->assertRedirect(route('logistica.count-runs.show', $conteo))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_count_runs', [
            'id' => $conteo->id,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseHas('inventory_count_run_items', [
            'inventory_count_run_id' => $conteo->id,
            'producto_id' => $producto->id,
            'stock_sistema' => 8,
            'cantidad_contada' => 6,
            'diferencia' => -2,
        ]);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 8]);

        $this->actingAs($vendedor)
            ->patch(route('logistica.review-count', $conteo), [
                'decision' => 'aprobar',
            ])
            ->assertForbidden();

        $this->actingAs($bodeguero)
            ->patch(route('logistica.review-count', $conteo), [
                'decision' => 'aprobar',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_count_runs', [
            'id' => $conteo->id,
            'estado' => 'aprobado',
            'revisado_por' => $bodeguero->id,
        ]);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 6]);
        $this->assertDatabaseHas('inventory_movements', [
            'producto_id' => $producto->id,
            'tipo' => 'correccion',
            'cantidad' => -2,
            'referencia' => 'CONTEO-'.$conteo->id,
        ]);
    }

    public function test_count_cannot_be_approved_after_a_later_inventory_movement(): void
    {
        $vendedor = User::factory()->create(['role' => 'vendedor']);
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $producto = $this->createProduct();

        $this->actingAs($vendedor)->post(route('logistica.count-runs.start'));
        $conteo = InventoryCountRun::firstOrFail();
        $this->put(route('logistica.count-runs.finish', $conteo), [
            'items' => [$producto->id => 6],
        ]);

        $producto->decrement('stock', 1);
        InventoryMovement::create([
            'user_id' => $bodeguero->id,
            'producto_id' => $producto->id,
            'tipo' => 'venta',
            'cantidad' => -1,
            'stock_before' => 8,
            'stock_after' => 7,
            'costo_unitario' => $producto->costo,
            'motivo' => 'Venta de prueba posterior al conteo',
        ]);

        $this->actingAs($bodeguero)
            ->from(route('logistica.index'))
            ->patch(route('logistica.review-count', $conteo), [
                'decision' => 'aprobar',
            ])
            ->assertRedirect(route('logistica.index'))
            ->assertSessionHasErrors('conteo');

        $this->assertDatabaseHas('inventory_count_runs', [
            'id' => $conteo->id,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 7]);
    }

    public function test_bodeguero_finishes_count_with_adjustment_and_loss_valuation(): void
    {
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $producto = $this->createProduct();

        $this->actingAs($bodeguero)->post(route('logistica.count-runs.start'));
        $conteo = InventoryCountRun::firstOrFail();

        $this->put(route('logistica.count-runs.finish', $conteo), [
            'items' => [$producto->id => 6],
        ])->assertRedirect(route('logistica.count-runs.show', $conteo));

        $this->assertDatabaseHas('inventory_count_runs', [
            'id' => $conteo->id,
            'estado' => 'aprobado',
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'stock' => 6,
        ]);

        $this->get(route('logistica.count-runs.show', $conteo))
            ->assertOk()
            ->assertSee('2 unidades')
            ->assertSee('$100.000');
    }

    private function createProduct(): Producto
    {
        $category = Categoria::create(['nombre' => 'Camisas']);

        return Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'STOCK-WORKFLOW-TEST',
            'nombre' => 'Camisa para logística',
            'talla' => 'M',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 8,
            'activo' => true,
        ]);
    }
}
