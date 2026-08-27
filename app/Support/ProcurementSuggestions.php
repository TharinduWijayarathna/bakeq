<?php

namespace App\Support;

use App\Models\Cake;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Support\Collection;

class ProcurementSuggestions
{
    /**
     * Combine demand forecast with recipes and stock to suggest reorders.
     *
     * @return array{
     *     days: int,
     *     until_label: string,
     *     expected_orders: float,
     *     items: list<array{
     *         ingredient_id: int,
     *         name: string,
     *         unit: string,
     *         current_stock: float,
     *         needed: float,
     *         shortage: float,
     *         suggested_qty: float,
     *         estimated_cost: int,
     *         supplier: string|null,
     *         reason: string
     *     }>,
     *     estimated_total_cost: int,
     *     summary: string
     * }
     */
    public static function untilSaturday(int $lookbackWeeks = 4): array
    {
        $days = self::daysUntilSaturday();

        return self::forDays($days, $lookbackWeeks);
    }

    /**
     * @return array{
     *     days: int,
     *     until_label: string,
     *     expected_orders: float,
     *     items: list<array{
     *         ingredient_id: int,
     *         name: string,
     *         unit: string,
     *         current_stock: float,
     *         needed: float,
     *         shortage: float,
     *         suggested_qty: float,
     *         estimated_cost: int,
     *         supplier: string|null,
     *         reason: string
     *     }>,
     *     estimated_total_cost: int,
     *     summary: string
     * }
     */
    public static function forDays(int $days, int $lookbackWeeks = 4): array
    {
        $days = max(1, $days);
        $expectedOrders = DemandForecast::expectedUnitsForDays($days, $lookbackWeeks);
        $mix = DemandForecast::cakeMix();
        $needs = self::ingredientNeeds($expectedOrders, $mix);
        $ingredients = Ingredient::query()
            ->whereIn('id', $needs->keys()->all())
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($needs as $ingredientId => $needed) {
            /** @var Ingredient|null $ingredient */
            $ingredient = $ingredients->get($ingredientId);

            if ($ingredient === null) {
                continue;
            }

            $stock = (float) $ingredient->current_stock;
            $threshold = (float) $ingredient->reorder_threshold;
            $shortage = max(0, $needed - $stock);
            // Cover forecasted usage, then restore the reorder threshold buffer.
            $suggested = max(0, round($needed + $threshold - $stock, 3));

            if ($suggested <= 0) {
                continue;
            }

            $estimatedCost = (int) round($suggested * $ingredient->unit_cost);
            $unit = $ingredient->unit->value;

            $items[] = [
                'ingredient_id' => $ingredient->id,
                'name' => $ingredient->name,
                'unit' => $unit,
                'current_stock' => $stock,
                'needed' => round($needed, 3),
                'shortage' => round($shortage, 3),
                'suggested_qty' => $suggested,
                'estimated_cost' => $estimatedCost,
                'supplier' => $ingredient->supplier,
                'reason' => sprintf(
                    "You'll likely need ~%s more %s before %s based on forecasted demand (and keeping the reorder buffer).",
                    self::qtyLabel($suggested, $unit),
                    $ingredient->name,
                    self::untilLabel($days),
                ),
            ];
        }

        usort($items, fn (array $a, array $b): int => $b['estimated_cost'] <=> $a['estimated_cost']);

        $total = (int) collect($items)->sum('estimated_cost');
        $untilLabel = self::untilLabel($days);

        $summary = $items === []
            ? sprintf(
                'Stock looks healthy for about %.1f expected orders through %s, based on the last %d weeks’ moving average and your recipes.',
                $expectedOrders,
                $untilLabel,
                $lookbackWeeks,
            )
            : sprintf(
                'Based on ~%.1f expected orders through %s (from a %d-week moving average) and current recipes, reorder %d ingredient%s, about %s total.',
                $expectedOrders,
                $untilLabel,
                $lookbackWeeks,
                count($items),
                count($items) === 1 ? '' : 's',
                Money::format($total),
            );

        return [
            'days' => $days,
            'until_label' => $untilLabel,
            'expected_orders' => $expectedOrders,
            'items' => $items,
            'estimated_total_cost' => $total,
            'summary' => $summary,
        ];
    }

    public static function daysUntilSaturday(): int
    {
        $today = now()->startOfDay();
        $saturday = $today->copy()->next('Saturday');

        if ($today->isSaturday()) {
            $saturday = $today->copy()->addWeek()->next('Saturday');

            // If today is Saturday, plan through next Saturday (7 days).
            return 7;
        }

        return max(1, (int) $today->diffInDays($saturday));
    }

    /**
     * @param  list<array{cake_id: int, name: string, quantity: int, share: float}>  $mix
     * @return Collection<int, float>
     */
    private static function ingredientNeeds(float $expectedOrders, array $mix): Collection
    {
        /** @var Collection<int, float> $needs */
        $needs = collect();

        if ($expectedOrders <= 0) {
            return $needs;
        }

        if ($mix === []) {
            // No cake history: use average recipe across active cakes with recipes.
            $recipes = Recipe::query()
                ->with('items')
                ->whereHas('cake', fn ($query) => $query->where('is_active', true))
                ->get();

            if ($recipes->isEmpty()) {
                return $needs;
            }

            $share = 1 / $recipes->count();

            foreach ($recipes as $recipe) {
                self::addRecipeNeeds($needs, $recipe, $expectedOrders * $share);
            }

            return $needs;
        }

        $cakes = Cake::query()
            ->with(['recipes.items'])
            ->whereIn('id', collect($mix)->pluck('cake_id'))
            ->get()
            ->keyBy('id');

        foreach ($mix as $row) {
            $cake = $cakes->get($row['cake_id']);

            if ($cake === null) {
                continue;
            }

            $recipe = $cake->recipes->first();

            if ($recipe === null) {
                continue;
            }

            self::addRecipeNeeds($needs, $recipe, $expectedOrders * $row['share']);
        }

        return $needs;
    }

    /**
     * @param  Collection<int, float>  $needs
     */
    private static function addRecipeNeeds(Collection $needs, Recipe $recipe, float $units): void
    {
        foreach ($recipe->items as $item) {
            $qty = ((float) $item->quantity) * $units;
            $needs[$item->ingredient_id] = ($needs[$item->ingredient_id] ?? 0) + $qty;
        }
    }

    private static function untilLabel(int $days): string
    {
        if ($days === self::daysUntilSaturday() && ! now()->isSaturday()) {
            return 'Saturday';
        }

        return now()->addDays($days)->format('l, M j');
    }

    private static function qtyLabel(float $qty, string $unit): string
    {
        $formatted = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');

        return $formatted.' '.$unit;
    }
}
