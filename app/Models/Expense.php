<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const STATUSES = [
        'paga',
        'pendente',
        'atrasada',
    ];

    public const CATEGORIES = [
        'moradia',
        'alimentacao',
        'transporte',
        'lazer',
        'saude',
        'educacao',
        'contas_fixas',
        'outros',
    ];

    protected $fillable = [
        'user_id',
        'description',
        'amount',
        'date',
        'category',
        'payment_method',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
