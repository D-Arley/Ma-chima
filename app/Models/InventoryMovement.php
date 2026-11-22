<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'user_id',
        'producto_id',
        'producto_nombre',
        'producto_codigo',
        'tipo',
        'cantidad',
        'stock_before',
        'stock_after',
        'costo_unitario',
        'motivo',
        'descripcion',
        'referencia',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
