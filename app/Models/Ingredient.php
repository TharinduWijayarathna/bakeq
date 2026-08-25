<?php

namespace App\Models;

use App\Enums\IngredientUnit;
use App\Support\Money;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property IngredientUnit $unit
 * @property string $current_stock
 * @property int $unit_cost
 * @property string|null $supplier
 * @property string $reorder_threshold
 * @property Carbon|null $expiry_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'unit',
    'current_stock',
    'unit_cost',
    'supplier',
    'reorder_threshold',
    'expiry_date',
])]
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'unit' => 'g',
        'current_stock' => 0,
        'unit_cost' => 0,
        'reorder_threshold' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => IngredientUnit::class,
            'current_stock' => 'decimal:3',
            'unit_cost' => 'integer',
            'reorder_threshold' => 'decimal:3',
            'expiry_date' => 'date',
        ];
    }

    /**
     * @param  Builder<Ingredient>  $query
     * @return Builder<Ingredient>
     */
    #[Scope]
    protected function lowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'reorder_threshold');
    }

    /**
     * @param  Builder<Ingredient>  $query
     * @return Builder<Ingredient>
     */
    #[Scope]
    protected function expiringSoon(Builder $query, int $days = 14): Builder
    {
        return $query
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->whereDate('expiry_date', '>=', now()->toDateString());
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_stock <= (float) $this->reorder_threshold;
    }

    public function isExpiringSoon(int $days = 14): bool
    {
        if ($this->expiry_date === null) {
            return false;
        }

        return $this->expiry_date->lessThanOrEqualTo(now()->addDays($days)->startOfDay())
            && $this->expiry_date->greaterThanOrEqualTo(now()->startOfDay());
    }

    public function formattedUnitCost(): string
    {
        return Money::format($this->unit_cost);
    }

    public function stockLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->current_stock, 3, '.', ''), '0'), '.').' '.$this->unit->value;
    }
}
