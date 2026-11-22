<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCount extends Model
{
    protected $fillable = [
        'user_id',
        'producto_id',
        'stock_sistema',
        'cantidad_contada',
        'diferencia',
        'ultimo_movimiento_id',
        'estado',
        'revisado_por',
        'revisado_at',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'stock_sistema' => 'integer',
            'cantidad_contada' => 'integer',
            'diferencia' => 'integer',
            'revisado_at' => 'datetime',
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

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
