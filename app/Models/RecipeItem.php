<?php

namespace App\Models;

use Database\Factories\RecipeItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $recipe_id
 * @property int $ingredient_id
 * @property string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Recipe $recipe
 * @property-read Ingredient $ingredient
 */
#[Fillable(['recipe_id', 'ingredient_id', 'quantity'])]
class RecipeItem extends Model
{
    /** @use HasFactory<RecipeItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function lineCostCents(): int
    {
        return (int) round(((float) $this->quantity) * $this->ingredient->unit_cost);
    }
}
