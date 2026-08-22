<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\DesignerSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $min_tiers
 * @property int $max_tiers
 * @property int $lead_days
 * @property string|null $notice
 * @property int $base_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['min_tiers', 'max_tiers', 'lead_days', 'notice', 'base_price'])]
class DesignerSetting extends Model
{
    /** @use HasFactory<DesignerSettingFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'min_tiers' => 1,
        'max_tiers' => 3,
        'lead_days' => 3,
        'base_price' => 450000,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_tiers' => 'integer',
            'max_tiers' => 'integer',
            'lead_days' => 'integer',
            'base_price' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'min_tiers' => 1,
            'max_tiers' => 3,
            'lead_days' => 3,
            'notice' => 'We bake custom cakes to order. Please allow a few days of lead time.',
            'base_price' => 450000,
        ]);
    }

    public function formattedBasePrice(): string
    {
        return Money::format($this->base_price);
    }
}
