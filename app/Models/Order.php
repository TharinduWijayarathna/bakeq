<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property OrderSource $order_source
 * @property OrderOrigin $origin
 * @property FulfillmentMethod $fulfillment_method
 * @property OrderStatus $status
 * @property int $subtotal
 * @property int $addons_total
 * @property int $delivery_fee
 * @property int $tax_amount
 * @property int $deposit_paid
 * @property int $total_due
 * @property Carbon $delivery_date
 * @property string $delivery_address
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'order_source',
    'origin',
    'fulfillment_method',
    'status',
    'subtotal',
    'addons_total',
    'delivery_fee',
    'tax_amount',
    'deposit_paid',
    'total_due',
    'delivery_date',
    'delivery_address',
    'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'order_source' => 'online',
        'origin' => 'catalog',
        'fulfillment_method' => 'delivery',
        'addons_total' => 0,
        'delivery_fee' => 0,
        'tax_amount' => 0,
        'deposit_paid' => 0,
        'total_due' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'order_source' => OrderSource::class,
            'origin' => OrderOrigin::class,
            'fulfillment_method' => FulfillmentMethod::class,
            'subtotal' => 'integer',
            'addons_total' => 'integer',
            'delivery_fee' => 'integer',
            'tax_amount' => 'integer',
            'deposit_paid' => 'integer',
            'total_due' => 'integer',
            'delivery_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal);
    }

    public function formattedTotalDue(): string
    {
        return Money::format($this->total_due > 0 ? $this->total_due : $this->subtotal);
    }

    public function amountDue(): int
    {
        return $this->total_due > 0 ? $this->total_due : $this->subtotal;
    }
}
