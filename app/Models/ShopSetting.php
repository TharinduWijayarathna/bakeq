<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\ShopSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delivery_fee
 * @property int $pickup_fee
 * @property string $tax_percent
 * @property string $deposit_percent
 * @property string $labor_overhead_percent
 * @property int $monthly_revenue_budget
 * @property string $business_name
 * @property string|null $business_address
 * @property string|null $business_phone
 * @property string|null $business_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'delivery_fee',
    'pickup_fee',
    'tax_percent',
    'deposit_percent',
    'labor_overhead_percent',
    'monthly_revenue_budget',
    'business_name',
    'business_address',
    'business_phone',
    'business_email',
])]
class ShopSetting extends Model
{
    /** @use HasFactory<ShopSettingFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'delivery_fee' => 50000,
        'pickup_fee' => 0,
        'tax_percent' => 0,
        'deposit_percent' => 0,
        'labor_overhead_percent' => 15,
        'monthly_revenue_budget' => 0,
        'business_name' => 'Bakeq Cakes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_fee' => 'integer',
            'pickup_fee' => 'integer',
            'tax_percent' => 'decimal:2',
            'deposit_percent' => 'decimal:2',
            'labor_overhead_percent' => 'decimal:2',
            'monthly_revenue_budget' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'delivery_fee' => 50000,
            'pickup_fee' => 0,
            'tax_percent' => 0,
            'deposit_percent' => 0,
            'labor_overhead_percent' => 15,
            'monthly_revenue_budget' => 0,
            'business_name' => 'Bakeq Cakes',
            'business_address' => 'Colombo, Sri Lanka',
            'business_phone' => '0767681678',
            'business_email' => 'hello@bakeq.test',
        ]);
    }

    public function formattedDeliveryFee(): string
    {
        return Money::format($this->delivery_fee);
    }

    public function formattedPickupFee(): string
    {
        return Money::format($this->pickup_fee);
    }

    public function formattedMonthlyRevenueBudget(): string
    {
        return Money::format($this->monthly_revenue_budget);
    }
}
