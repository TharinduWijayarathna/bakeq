<?php

namespace App\Livewire\Admin;

use App\Models\Cake;
use App\Models\ShopSetting;
use App\Support\CakeCosting;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Cakes')]
class CakeIndex extends Component
{
    public function delete(int $cakeId): void
    {
        Cake::query()->findOrFail($cakeId)->delete();
        session()->flash('status', 'Cake removed.');
    }

    public function render(): View
    {
        $settings = ShopSetting::current();

        $cakes = Cake::query()
            ->with(['category', 'recipes.items.ingredient'])
            ->latest()
            ->get()
            ->map(function (Cake $cake) use ($settings): array {
                $costing = CakeCosting::forCake($cake, $settings);

                return [
                    'cake' => $cake,
                    'costing' => $costing,
                    'formatted_cost' => $costing ? Money::format($costing['total_cost']) : null,
                    'formatted_profit' => $costing ? Money::format($costing['profit']) : null,
                    'margin_percent' => $costing['margin_percent'] ?? null,
                ];
            });

        return view('livewire.admin.cake-index', [
            'cakes' => $cakes,
            'laborPercent' => $settings->labor_overhead_percent,
        ]);
    }
}
