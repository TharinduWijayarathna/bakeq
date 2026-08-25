<?php

namespace App\Support;

use App\Models\Cake;
use App\Models\Recipe;
use App\Models\ShopSetting;

class CakeCosting
{
    /**
     * Ingredient cost + labor/overhead % from shop settings.
     *
     * @return array{
     *     ingredient_cost: int,
     *     labor_cost: int,
     *     total_cost: int,
     *     sale_price: int,
     *     profit: int,
     *     margin_percent: float
     * }
     */
    public static function forRecipe(Recipe $recipe, ?ShopSetting $settings = null): array
    {
        $settings ??= ShopSetting::current();
        $recipe->loadMissing(['items.ingredient', 'cake']);

        $ingredientCost = $recipe->items->sum(
            fn ($item): int => (int) round(((float) $item->quantity) * $item->ingredient->unit_cost),
        );

        $laborPercent = (float) $settings->labor_overhead_percent;
        $laborCost = (int) round($ingredientCost * ($laborPercent / 100));
        $totalCost = $ingredientCost + $laborCost;
        $salePrice = self::salePriceForRecipe($recipe);
        $profit = $salePrice - $totalCost;
        $marginPercent = $salePrice > 0
            ? round(($profit / $salePrice) * 100, 1)
            : 0.0;

        return [
            'ingredient_cost' => $ingredientCost,
            'labor_cost' => $laborCost,
            'total_cost' => $totalCost,
            'sale_price' => $salePrice,
            'profit' => $profit,
            'margin_percent' => $marginPercent,
        ];
    }

    /**
     * @return array{
     *     ingredient_cost: int,
     *     labor_cost: int,
     *     total_cost: int,
     *     sale_price: int,
     *     profit: int,
     *     margin_percent: float
     * }|null
     */
    public static function forCake(Cake $cake, ?ShopSetting $settings = null): ?array
    {
        $recipe = $cake->relationLoaded('recipes')
            ? $cake->recipes->first()
            : $cake->recipes()->with('items.ingredient')->orderBy('id')->first();

        if ($recipe === null) {
            return null;
        }

        if (! $recipe->relationLoaded('items')) {
            $recipe->load('items.ingredient');
        }

        $recipe->setRelation('cake', $cake);

        return self::forRecipe($recipe, $settings);
    }

    private static function salePriceForRecipe(Recipe $recipe): int
    {
        $cake = $recipe->cake;

        if ($cake === null) {
            return 0;
        }

        if (filled($recipe->size_label)) {
            foreach ($cake->size_options ?? [] as $size) {
                if (! is_array($size)) {
                    continue;
                }

                if (($size['label'] ?? '') === $recipe->size_label) {
                    return (int) ($size['price'] ?? $cake->price);
                }
            }
        }

        return $cake->price;
    }
}
