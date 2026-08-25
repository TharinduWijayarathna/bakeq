<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property string $number
 * @property Carbon $issued_at
 * @property int $subtotal
 * @property int $discount_amount
 * @property int $delivery_fee
 * @property int $tax_amount
 * @property int $deposit_paid
 * @property int $total_due
 * @property array<int, array<string, mixed>> $line_items
 * @property array<string, mixed>|null $business_snapshot
 * @property array<string, mixed>|null $customer_snapshot
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 */
#[Fillable([
    'order_id',
    'number',
    'issued_at',
    'subtotal',
    'discount_amount',
    'delivery_fee',
    'tax_amount',
    'deposit_paid',
    'total_due',
    'line_items',
    'business_snapshot',
    'customer_snapshot',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'delivery_fee' => 'integer',
            'tax_amount' => 'integer',
            'deposit_paid' => 'integer',
            'total_due' => 'integer',
            'line_items' => 'array',
            'business_snapshot' => 'array',
            'customer_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function formattedTotalDue(): string
    {
        return Money::format($this->total_due);
    }
}
