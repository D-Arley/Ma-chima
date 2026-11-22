<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogo = [
            'Zapatos' => [
                ['Zapato Oxford Negro', 'Oxford clásico de cuero sintético con acabado pulido.', '39', 249900, 138000, 8, '/images/products/oxford-negro.png'],
                ['Tenis Urbano Blanco', 'Tenis casual blanco para uso diario y uniforme moderno.', '40', 189900, 98000, 12, '/images/products/tenis-blanco.jpg'],
                ['Botín Chelsea Marrón', 'Botín alto con elástico lateral y suela resistente.', '41', 279900, 154000, 6, '/images/products/botin-chelsea.jpg'],
                ['Tacón Fino Champagne', 'Tacón elegante de 8 cm con diseño minimalista.', '38', 219900, 119000, 7, '/images/products/tacon-champagne.jpg'],
                ['Sandalia Verano Dorada', 'Sandalia ligera con hebilla ajustable y acabado premium.', '37', 159900, 79000, 10, '/images/products/sandalia-dorada.jpg'],
            ],
            'Pantalones' => [
                ['Pantalón Cargo Arena', 'Cargo de corte holgado con bolsillos laterales funcionales.', 'M', 189900, 92000, 10, '/images/products/sastrero-beige.jpg'],
                ['Jeans Slim Azul Oscuro', 'Jeans de mezclilla elástica con lavado intenso.', '32', 179900, 87000, 14, '/images/products/jeans-azul.jpg'],
                ['Pantalón Palazzo Negro', 'Palazzo de pierna amplia con cintura de pinza.', 'L', 169900, 81000, 9, '/images/products/palazzo-negro.jpg'],
                ['Jogger Urbano Gris', 'Jogger confortable con puños ajustados y bolsillo lateral.', 'M', 149900, 72000, 15, '/images/products/jogger-urbano.jpg'],
                ['Pantalón Sastrero Beige', 'Sastre de corte recto con textura suave y moderna.', '34', 199900, 98000, 8, '/images/products/sastrero-beige.jpg'],
            ],
            'Camisas' => [
                ['Camisa Oxford Celeste', 'Camisa Oxford de manga larga con corte clásico.', 'M', 139900, 65000, 12, '/images/products/camisa-oxford-celeste.jpg'],
                ['Camisa Oversize Blanca', 'Camisa blanca oversize con caída relajada.', 'L', 149900, 69000, 11, '/images/products/camisa-oversize-blanca.jpg'],
                ['Camisa Linen Natural', 'Camisa de lino fresco con bolsillo de parche.', 'M', 179900, 86000, 8, '/images/products/camisa-linen-natural.jpg'],
                ['Camisa Negra Satinada', 'Camisa negra de acabado satinado para ocasiones especiales.', 'S', 159900, 76000, 7, '/images/products/camisa-negra-satinada.jpg'],
                ['Camisa Rayas Navy', 'Camisa de rayas verticales con collar flexible.', 'XL', 169900, 79000, 9, '/images/products/camisa-rayas-navy.jpg'],
            ],
            'Camisetas' => [
                ['Camiseta Essential Negra', 'Camiseta básica de algodón con etiqueta exterior discreta.', 'M', 69900, 28000, 20, '/images/products/camiseta-essential-negra.jpg'],
                ['Camiseta Premium Beige', 'Camiseta premium de cuello redondo y tejido suave.', 'L', 89900, 39000, 18, '/images/products/camiseta-premium-beige.jpg'],
                ['Camiseta Oversize Grafito', 'Camiseta oversize con hombro caído y corte urbano.', 'XL', 99000, 44000, 14, '/images/products/camiseta-oversize-grafito.jpg'],
                ['Camiseta Rayas Champagne', 'Camiseta de rayas finas con silueta moderna.', 'S', 79900, 33000, 16, '/images/products/camiseta-rayas-champagne.jpg'],
                ['Camiseta Polo Verde', 'Polo de piqué con detalle bordado y botones ocultos.', 'M', 119900, 52000, 12, '/images/products/polo-verde.jpg'],
            ],
        ];

        DB::transaction(function () use ($catalogo): void {
            foreach (array_keys($catalogo) as $categoriaNombre) {
                Categoria::firstOrCreate(['nombre' => $categoriaNombre]);
            }

            Categoria::query()->orderBy('id')->lockForUpdate()->get(['id']);

            foreach ($catalogo as $categoriaNombre => $productos) {
                $categoria = Categoria::where('nombre', $categoriaNombre)->firstOrFail();

                foreach ($productos as [$nombre, $descripcion, $talla, $precio, $costo, $stock, $imagen]) {
                    $producto = Producto::firstOrNew([
                        'categoria_id' => $categoria->id,
                        'nombre' => $nombre,
                        'talla' => $talla,
                    ]);

                    if (! $producto->exists) {
                        $producto->fill([
                            'codigo' => null,
                            'descripcion' => $descripcion,
                            'precio' => $precio,
                            'costo' => $costo,
                            'stock' => $stock,
                            'activo' => true,
                        ]);
                    }

                    $producto->imagen = $imagen;
                    $producto->save();
                }
            }

            Producto::resequenceActiveCodes();
        });
    }
}
