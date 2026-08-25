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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['delivery_fee', 'pickup_fee', 'tax_percent', 'deposit_percent'])]
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
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'delivery_fee' => 50000,
            'pickup_fee' => 0,
            'tax_percent' => 0,
            'deposit_percent' => 0,
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
}
