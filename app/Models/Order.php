<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property OrderSource $order_source
 * @property OrderOrigin $origin
 * @property FulfillmentMethod $fulfillment_method
 * @property OrderStatus $status
 * @property ProductionStatus $production_status
 * @property int $subtotal
 * @property int $addons_total
 * @property int $delivery_fee
 * @property int $tax_amount
 * @property int $deposit_paid
 * @property int $total_due
 * @property bool $stock_deducted
 * @property DiscountType|null $discount_type
 * @property int $discount_value
 * @property int $discount_amount
 * @property PaymentMethod|null $payment_method
 * @property PaymentStatus $payment_status
 * @property int $payment_amount
 * @property string|null $stripe_checkout_id
 * @property string|null $stripe_payment_id
 * @property Carbon|null $paid_at
 * @property string|null $receipt_number
 * @property Carbon $delivery_date
 * @property string $delivery_address
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Invoice|null $invoice
 */
#[Fillable([
    'user_id',
    'order_source',
    'origin',
    'fulfillment_method',
    'status',
    'production_status',
    'subtotal',
    'addons_total',
    'delivery_fee',
    'tax_amount',
    'deposit_paid',
    'total_due',
    'stock_deducted',
    'discount_type',
    'discount_value',
    'discount_amount',
    'payment_method',
    'payment_status',
    'payment_amount',
    'stripe_checkout_id',
    'stripe_payment_id',
    'paid_at',
    'receipt_number',
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
        'production_status' => 'planning',
        'order_source' => 'online',
        'origin' => 'catalog',
        'fulfillment_method' => 'delivery',
        'addons_total' => 0,
        'delivery_fee' => 0,
        'tax_amount' => 0,
        'deposit_paid' => 0,
        'total_due' => 0,
        'stock_deducted' => false,
        'discount_value' => 0,
        'discount_amount' => 0,
        'payment_status' => 'unpaid',
        'payment_amount' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'production_status' => ProductionStatus::class,
            'order_source' => OrderSource::class,
            'origin' => OrderOrigin::class,
            'fulfillment_method' => FulfillmentMethod::class,
            'discount_type' => DiscountType::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'addons_total' => 'integer',
            'delivery_fee' => 'integer',
            'tax_amount' => 'integer',
            'deposit_paid' => 'integer',
            'total_due' => 'integer',
            'stock_deducted' => 'boolean',
            'discount_value' => 'integer',
            'discount_amount' => 'integer',
            'payment_amount' => 'integer',
            'delivery_date' => 'date',
            'paid_at' => 'datetime',
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

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal);
    }

    public function formattedTotalDue(): string
    {
        return Money::format($this->total_due > 0 ? $this->total_due : $this->subtotal);
    }

    public function formattedPaymentAmount(): string
    {
        return Money::format($this->payment_amount);
    }

    public function amountDue(): int
    {
        return $this->total_due > 0 ? $this->total_due : $this->subtotal;
    }

    public function hasOutstandingBalance(): bool
    {
        return in_array($this->payment_status, [
            PaymentStatus::Unpaid,
            PaymentStatus::PartiallyPaid,
            PaymentStatus::AwaitingPayment,
            PaymentStatus::Failed,
        ], true) && $this->total_due > 0;
    }
}
