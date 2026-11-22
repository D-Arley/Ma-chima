<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'categoria_id',
        'codigo',
        'nombre',
        'descripcion',
        'talla',
        'precio',
        'costo',
        'stock',
        'imagen',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'costo' => 'decimal:2',
            'stock' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public static function nextSequentialCode(): string
    {
        return str_pad(
            (string) (static::query()->where('activo', true)->count() + 1),
            4,
            '0',
            STR_PAD_LEFT
        );
    }

    public static function resequenceActiveCodes(): void
    {
        $products = static::query()
            ->where('activo', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);

        static::query()->where('activo', true)->update(['codigo' => null]);

        foreach ($products as $index => $product) {
            static::query()
                ->whereKey($product->id)
                ->update(['codigo' => str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
