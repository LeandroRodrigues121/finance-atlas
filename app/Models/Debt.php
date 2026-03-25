<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pendente',
        'paga',
        'atrasada',
    ];

    protected $fillable = [
        'user_id',
        'description',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Debt $debt): void {
            $totalAmount = (float) ($debt->total_amount ?? 0);
            $paidAmount = (float) ($debt->paid_amount ?? 0);
            $debt->remaining_amount = max($totalAmount - $paidAmount, 0);

            if ($debt->remaining_amount <= 0) {
                $debt->status = 'paga';

                return;
            }

            if ($debt->due_date && $debt->due_date->isPast() && $debt->status !== 'paga') {
                $debt->status = 'atrasada';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
