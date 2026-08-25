<?php

namespace App\Models;

use App\Enums\WasteReason;
use App\Support\Money;
use Database\Factories\WasteEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $wasted_on
 * @property int|null $ingredient_id
 * @property int|null $cake_id
 * @property string $quantity
 * @property WasteReason $reason
 * @property int $cost_impact
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'wasted_on',
    'ingredient_id',
    'cake_id',
    'quantity',
    'reason',
    'cost_impact',
    'notes',
])]
class WasteEntry extends Model
{
    /** @use HasFactory<WasteEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wasted_on' => 'date',
            'quantity' => 'decimal:3',
            'reason' => WasteReason::class,
            'cost_impact' => 'integer',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function cake(): BelongsTo
    {
        return $this->belongsTo(Cake::class);
    }

    public function label(): string
    {
        return $this->ingredient?->name ?? $this->cake?->name ?? 'Item';
    }

    public function formattedCostImpact(): string
    {
        return Money::format($this->cost_impact);
    }

    public static function computeCostImpact(?Ingredient $ingredient, ?Cake $cake, float $quantity): int
    {
        if ($ingredient !== null) {
            return (int) round($quantity * $ingredient->unit_cost);
        }

        if ($cake !== null) {
            return (int) round($quantity * $cake->price);
        }

        return 0;
    }
}
