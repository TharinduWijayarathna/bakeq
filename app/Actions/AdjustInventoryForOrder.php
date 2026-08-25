<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdjustInventoryForOrder
{
    public function applyConfirmation(Order $order): void
    {
        if ($order->stock_deducted) {
            return;
        }

        $requirements = $this->requirements($order);

        if ($requirements->isEmpty()) {
            $order->update(['stock_deducted' => true]);

            return;
        }

        $shortfalls = $this->shortfalls($requirements);

        if ($shortfalls->isNotEmpty()) {
            throw new InsufficientStockException($shortfalls);
        }

        DB::transaction(function () use ($order, $requirements): void {
            foreach ($requirements as $ingredientId => $needed) {
                $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($ingredientId);
                $ingredient->update([
                    'current_stock' => (float) $ingredient->current_stock - $needed,
                ]);
            }

            $order->update(['stock_deducted' => true]);
        });
    }

    public function reverseConfirmation(Order $order): void
    {
        if (! $order->stock_deducted) {
            return;
        }

        $requirements = $this->requirements($order);

        DB::transaction(function () use ($order, $requirements): void {
            foreach ($requirements as $ingredientId => $needed) {
                $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($ingredientId);
                $ingredient->update([
                    'current_stock' => (float) $ingredient->current_stock + $needed,
                ]);
            }

            $order->update(['stock_deducted' => false]);
        });
    }

    public function syncForStatusChange(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        $becomingConfirmed = $to === OrderStatus::Confirmed && $from !== OrderStatus::Confirmed;
        $leavingConfirmedForCancel = $from === OrderStatus::Confirmed && $to === OrderStatus::Cancelled;

        if ($becomingConfirmed) {
            $this->applyConfirmation($order);

            return;
        }

        if ($leavingConfirmedForCancel) {
            $this->reverseConfirmation($order);
        }
    }

    /**
     * @return Collection<int, float> ingredient_id => quantity needed
     */
    public function requirements(Order $order): Collection
    {
        $order->loadMissing(['items.cake.recipes.items']);

        /** @var Collection<int, float> $totals */
        $totals = collect();

        foreach ($order->items as $item) {
            $recipe = $this->recipeForItem($item);

            if ($recipe === null) {
                continue;
            }

            $recipe->loadMissing('items');

            foreach ($recipe->items as $recipeItem) {
                $needed = ((float) $recipeItem->quantity) * $item->quantity;
                $current = (float) $totals->get($recipeItem->ingredient_id, 0.0);
                $totals->put($recipeItem->ingredient_id, $current + $needed);
            }
        }

        return $totals;
    }

    /**
     * @param  Collection<int, float>  $requirements
     * @return Collection<int, array{ingredient: string, needed: float, available: float, short: float, unit: string}>
     */
    public function shortfalls(Collection $requirements): Collection
    {
        if ($requirements->isEmpty()) {
            return collect();
        }

        $ingredients = Ingredient::query()
            ->whereIn('id', $requirements->keys())
            ->get()
            ->keyBy('id');

        return $requirements
            ->map(function (float $needed, int $ingredientId) use ($ingredients): ?array {
                $ingredient = $ingredients->get($ingredientId);

                if ($ingredient === null) {
                    return [
                        'ingredient' => 'Unknown #'.$ingredientId,
                        'needed' => $needed,
                        'available' => 0.0,
                        'short' => $needed,
                        'unit' => '',
                    ];
                }

                $available = (float) $ingredient->current_stock;

                if ($available >= $needed) {
                    return null;
                }

                return [
                    'ingredient' => $ingredient->name,
                    'needed' => $needed,
                    'available' => $available,
                    'short' => $needed - $available,
                    'unit' => $ingredient->unit->value,
                ];
            })
            ->filter()
            ->values();
    }

    private function recipeForItem(OrderItem $item): ?Recipe
    {
        if ($item->cake_id === null) {
            return null;
        }

        $cake = $item->cake;

        if ($cake === null) {
            return null;
        }

        $recipes = $cake->relationLoaded('recipes')
            ? $cake->recipes
            : $cake->recipes()->with('items')->get();

        if ($recipes->isEmpty()) {
            return null;
        }

        $sizeLabel = is_array($item->selections) ? (string) ($item->selections['size_label'] ?? '') : '';

        if ($sizeLabel !== '') {
            $match = $recipes->firstWhere('size_label', $sizeLabel);

            if ($match !== null) {
                return $match;
            }
        }

        return $recipes->firstWhere('size_label', '') ?? $recipes->first();
    }
}
