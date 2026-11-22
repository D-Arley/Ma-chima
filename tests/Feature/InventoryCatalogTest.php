<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_codes_are_generated_and_duplicate_products_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoria = Categoria::create(['nombre' => 'Camisas']);
        $data = [
            'categoria_id' => $categoria->id,
            'codigo' => 'MANUAL-01',
            'nombre' => 'Camisa Oxford',
            'talla' => 'M',
            'precio' => 100000,
            'costo' => 50000,
            'stock' => 4,
        ];

        $this->actingAs($admin)
            ->post(route('inventario.store'), $data)
            ->assertRedirect(route('inventario.index'));

        $producto = Producto::firstOrFail();
        $this->assertSame('0001', $producto->codigo);

        $this->actingAs($admin)
            ->post(route('inventario.store'), $data)
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Producto::count());
    }

    public function test_archiving_requires_zero_stock_and_resequences_active_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoria = Categoria::create(['nombre' => 'Zapatos']);
        $first = $this->createProduct($categoria, 'Tenis blancos', '38', 3);
        $second = $this->createProduct($categoria, 'Botas negras', '39', 0);

        $this->actingAs($admin)
            ->delete(route('inventario.destroy', $first))
            ->assertSessionHasErrors('producto');

        $first->update(['stock' => 0]);

        $this->delete(route('inventario.destroy', $first))
            ->assertRedirect(route('inventario.index'));

        $this->assertDatabaseHas('productos', [
            'id' => $first->id,
            'activo' => false,
            'codigo' => null,
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $second->id,
            'activo' => true,
            'codigo' => '0001',
        ]);
    }

    public function test_inventory_page_displays_total_active_stock_value_at_cost(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoria = Categoria::create(['nombre' => 'Pantalones']);
        $this->createProduct($categoria, 'Pantalón recto', 'M', 5, 12000);

        $this->actingAs($admin)
            ->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('$60.000');
    }

    private function createProduct(
        Categoria $categoria,
        string $nombre,
        string $talla,
        int $stock,
        int $costo = 10000
    ): Producto {
        return Producto::create([
            'categoria_id' => $categoria->id,
            'codigo' => Producto::nextSequentialCode(),
            'nombre' => $nombre,
            'talla' => $talla,
            'precio' => $costo * 2,
            'costo' => $costo,
            'stock' => $stock,
            'activo' => true,
        ]);
    }
}
