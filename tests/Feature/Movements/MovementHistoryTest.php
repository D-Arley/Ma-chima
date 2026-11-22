<?php

namespace Tests\Feature\Movements;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\Categoria;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MovementHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_range_includes_both_boundary_days_and_hides_other_movements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct();

        $this->createInventoryMovement($admin, $product, 'ENTRADA-INICIO', '2026-10-01 00:00:00');
        $this->createInventoryMovement($admin, $product, 'ENTRADA-FIN', '2026-10-31 23:59:59');
        $this->createInventoryMovement($admin, $product, 'ENTRADA-EXTERNA', '2026-09-30 23:59:59');

        $this->actingAs($admin)
            ->get('/movimientos?desde=2026-10-01&hasta=2026-10-31')
            ->assertOk()
            ->assertSee('ENTRADA-INICIO')
            ->assertSee('ENTRADA-FIN')
            ->assertDontSee('ENTRADA-EXTERNA');
    }

    public function test_cash_movements_are_searchable_by_the_user_and_rendered_in_the_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'vendedor', 'name' => 'Vendedor de prueba']);
        $caja = Caja::create([
            'user_id' => $seller->id,
            'fecha' => '2026-10-10',
            'opening_cash' => 200000,
            'cash_balance' => 200000,
            'transfer_balance' => 0,
            'bank_balance' => 0,
            'status' => 'cerrada',
            'opened_at' => '2026-10-10 08:00:00',
            'closed_at' => '2026-10-10 18:00:00',
        ]);
        $movement = CajaMovimiento::create([
            'caja_id' => $caja->id,
            'user_id' => $seller->id,
            'tipo' => 'transferencia_banco',
            'amount' => 50000,
            'cash_after' => 200000,
            'bank_after' => 50000,
            'descripcion' => 'Traslado de prueba',
            'fecha' => '2026-10-10 12:00:00',
        ]);

        $this->actingAs($admin)
            ->get('/movimientos?buscar=Vendedor%20de%20prueba&desde=2026-10-10&hasta=2026-10-10')
            ->assertOk()
            ->assertSee('CAJA-'.$movement->id)
            ->assertSee('Traslado de prueba');
    }

    public function test_excel_download_contains_formatted_summary_and_filtered_movement_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct();
        $product->update(['nombre' => 'Camisa & <historial>']);
        $this->createInventoryMovement($admin, $product, 'EXCEL-ENTRADA', '2026-10-10 09:00:00');
        $this->createInventoryMovement($admin, $product, 'EXCEL-EXCLUIDO', '2026-09-10 09:00:00');
        Venta::create([
            'user_id' => $admin->id,
            'comprobante' => 'VEN-EXCEL-001',
            'subtotal' => 100000,
            'descuento' => 5000,
            'iva' => 15161.34,
            'ganancia' => 30000,
            'total' => 95000,
            'metodo_pago' => 'efectivo',
            'efectivo_recibido' => 100000,
            'cambio' => 5000,
            'fecha' => '2026-10-10 10:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/movimientos/exportar/excel?desde=2026-10-01&hasta=2026-10-31');

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertDownload();

        $workbook = $response->streamedContent();
        $document = new \DOMDocument;
        $this->assertTrue($document->loadXML($workbook));
        $this->assertStringContainsString('Excel.Sheet', $workbook);
        $this->assertStringContainsString('ss:Name="Resumen"', $workbook);
        $this->assertStringContainsString('ss:Name="Movimientos"', $workbook);
        $this->assertStringContainsString('Camisa &amp; &lt;historial&gt;', $workbook);
        $this->assertStringContainsString('Ganancia neta de ventas (COP)', $workbook);
        $this->assertStringContainsString('IVA de venta (COP)', $workbook);
        $this->assertStringContainsString('Descuento (COP)', $workbook);
        $this->assertStringContainsString('30000.00', $workbook);
        $this->assertStringContainsString('VEN-EXCEL-001', $workbook);
        $this->assertStringContainsString('EXCEL-ENTRADA', $workbook);
        $this->assertStringNotContainsString('EXCEL-EXCLUIDO', $workbook);
    }

    public function test_invalid_or_reversed_date_ranges_return_validation_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/movimientos')
            ->get('/movimientos?desde=2026-11-02&hasta=2026-11-01')
            ->assertRedirect('/movimientos')
            ->assertSessionHasErrors('hasta');
    }

    public function test_current_movement_route_marks_its_navigation_link_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/movimientos')
            ->assertOk()
            ->assertSee('Movimientos')
            ->assertSee('border-[#d2aa5c] text-[#e4c577]', false);
    }

    private function createProduct(): Producto
    {
        $category = Categoria::create(['nombre' => 'Camisas']);

        return Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'CMS-HISTORY-TEST',
            'nombre' => 'Camisa para historial',
            'talla' => 'M',
            'precio' => 119000,
            'costo' => 50000,
            'stock' => 8,
            'activo' => true,
        ]);
    }

    private function createInventoryMovement(User $user, Producto $product, string $reference, string $date): void
    {
        $movement = InventoryMovement::create([
            'user_id' => $user->id,
            'producto_id' => $product->id,
            'tipo' => 'entrada',
            'cantidad' => 1,
            'stock_before' => 7,
            'stock_after' => 8,
            'costo_unitario' => $product->costo,
            'motivo' => 'Ingreso de prueba',
            'descripcion' => 'Movimiento de prueba',
            'referencia' => $reference,
        ]);
        $movement->created_at = Carbon::parse($date);
        $movement->updated_at = Carbon::parse($date);
        $movement->save();
    }
}
