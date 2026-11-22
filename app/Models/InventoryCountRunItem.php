<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountRunItem extends Model
{
    protected $fillable = [
        'inventory_count_run_id',
        'producto_id',
        'producto_nombre',
        'producto_codigo',
        'stock_sistema',
        'costo_unitario',
        'ultimo_movimiento_id',
        'cantidad_contada',
        'diferencia',
    ];

    protected function casts(): array
    {
        return [
            'stock_sistema' => 'integer',
            'costo_unitario' => 'decimal:2',
            'cantidad_contada' => 'integer',
            'diferencia' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(InventoryCountRun::class, 'inventory_count_run_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
