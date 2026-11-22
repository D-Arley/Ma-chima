<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CajaMovimiento extends Model
{
    protected $table = 'caja_movimientos';

    protected $fillable = [
        'caja_id',
        'user_id',
        'movable_type',
        'movable_id',
        'tipo',
        'amount',
        'cash_after',
        'bank_after',
        'descripcion',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cash_after' => 'decimal:2',
            'bank_after' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }
}
