<?php

namespace App\Models;

use App\Support\CakeCosting;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $cake_id
 * @property string|null $name
 * @property string|null $size_label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cake $cake
 */
#[Fillable(['cake_id', 'name', 'size_label'])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'size_label' => '',
    ];

    public function cake(): BelongsTo
    {
        return $this->belongsTo(Cake::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function displayName(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        $label = $this->cake?->name ?? 'Cake';

        return filled($this->size_label) ? $label.' · '.$this->size_label : $label;
    }

    /**
     * @return array{ingredient_cost: int, labor_cost: int, total_cost: int, sale_price: int, profit: int, margin_percent: float}
     */
    public function costing(?ShopSetting $settings = null): array
    {
        return CakeCosting::forRecipe($this, $settings);
    }
}
