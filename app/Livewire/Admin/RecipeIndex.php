<?php

namespace App\Livewire\Admin;

use App\Models\Recipe;
use App\Support\CakeCosting;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Recipes')]
class RecipeIndex extends Component
{
    public function delete(int $recipeId): void
    {
        Recipe::query()->findOrFail($recipeId)->delete();
        session()->flash('status', 'Recipe removed.');
    }

    public function render(): View
    {
        $recipes = Recipe::query()
            ->with(['cake', 'items.ingredient'])
            ->latest()
            ->get()
            ->map(function (Recipe $recipe): array {
                $costing = CakeCosting::forRecipe($recipe);

                return [
                    'recipe' => $recipe,
                    'costing' => $costing,
                    'formatted_cost' => Money::format($costing['total_cost']),
                    'formatted_profit' => Money::format($costing['profit']),
                    'formatted_sale' => Money::format($costing['sale_price']),
                ];
            });

        return view('livewire.admin.recipe-index', [
            'recipes' => $recipes,
        ]);
    }
}
