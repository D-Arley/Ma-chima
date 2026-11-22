<?php

namespace Tests\Feature\Catalogo;

use App\Models\Categoria;
use App\Models\Producto;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeder_updates_existing_images_without_resetting_product_data(): void
    {
        $category = Categoria::create(['nombre' => 'Zapatos']);
        $product = Producto::create([
            'categoria_id' => $category->id,
            'codigo' => 'ZAP-001',
            'nombre' => 'Zapato Oxford Negro',
            'descripcion' => 'Descripción editada en bodega.',
            'talla' => '39',
            'precio' => 289000,
            'costo' => 141000,
            'stock' => 3,
            'activo' => true,
            'imagen' => 'imagen-anterior.jpg',
        ]);

        $this->seed(CatalogoSeeder::class);
        $this->seed(CatalogoSeeder::class);

        $product->refresh();
        $this->assertSame(20, Producto::count());
        $this->assertSame('Zapato Oxford Negro', $product->nombre);
        $this->assertSame('Descripción editada en bodega.', $product->descripcion);
        $this->assertSame('39', $product->talla);
        $this->assertSame('289000.00', $product->precio);
        $this->assertSame('141000.00', $product->costo);
        $this->assertSame(3, $product->stock);
        $this->assertSame('/images/products/oxford-negro.png', $product->imagen);
    }
}
