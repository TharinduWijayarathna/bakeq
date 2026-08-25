<?php

namespace App\Livewire\Admin;

use App\Enums\IngredientUnit;
use App\Models\Ingredient;
use App\Models\ShopSetting;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Inventory')]
class InventoryIndex extends Component
{
    #[Url]
    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $unit = 'g';

    public string $current_stock = '0';

    public string $unit_cost_rupees = '0';

    public string $supplier = '';

    public string $reorder_threshold = '0';

    public string $expiry_date = '';

    public string $labor_overhead_percent = '15';

    public function mount(): void
    {
        $this->labor_overhead_percent = (string) ShopSetting::current()->labor_overhead_percent;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::enum(IngredientUnit::class)],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'unit_cost_rupees' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'reorder_threshold' => ['required', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'current_stock' => $validated['current_stock'],
            'unit_cost' => (int) round(((float) $validated['unit_cost_rupees']) * 100),
            'supplier' => $validated['supplier'] ?: null,
            'reorder_threshold' => $validated['reorder_threshold'],
            'expiry_date' => $validated['expiry_date'] ?: null,
        ];

        if ($this->editingId !== null) {
            $ingredient = Ingredient::query()->findOrFail($this->editingId);
            $old = [
                'current_stock' => $ingredient->current_stock,
                'unit_cost' => $ingredient->unit_cost,
            ];
            $ingredient->update($payload);
            AuditLogger::record('inventory.ingredient_updated', $ingredient, $old, [
                'current_stock' => $payload['current_stock'],
                'unit_cost' => $payload['unit_cost'],
            ]);
        } else {
            $ingredient = Ingredient::query()->create($payload);
            AuditLogger::record('inventory.ingredient_created', $ingredient, null, [
                'name' => $ingredient->name,
                'current_stock' => $ingredient->current_stock,
            ]);
        }

        $this->resetForm();
        session()->flash('status', 'Ingredient saved.');
    }

    public function edit(int $ingredientId): void
    {
        $ingredient = Ingredient::query()->findOrFail($ingredientId);
        $this->editingId = $ingredient->id;
        $this->name = $ingredient->name;
        $this->unit = $ingredient->unit->value;
        $this->current_stock = (string) $ingredient->current_stock;
        $this->unit_cost_rupees = (string) round($ingredient->unit_cost / 100, 2);
        $this->supplier = $ingredient->supplier ?? '';
        $this->reorder_threshold = (string) $ingredient->reorder_threshold;
        $this->expiry_date = $ingredient->expiry_date?->toDateString() ?? '';
    }

    public function delete(int $ingredientId): void
    {
        Ingredient::query()->findOrFail($ingredientId)->delete();
        session()->flash('status', 'Ingredient removed.');
    }

    public function saveLaborOverhead(): void
    {
        $validated = $this->validate([
            'labor_overhead_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $settings = ShopSetting::current();
        $settings->update([
            'labor_overhead_percent' => $validated['labor_overhead_percent'],
        ]);

        session()->flash('status', 'Labor / overhead percentage updated.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'name',
            'unit',
            'current_stock',
            'unit_cost_rupees',
            'supplier',
            'reorder_threshold',
            'expiry_date',
        ]);
        $this->unit = 'g';
        $this->current_stock = '0';
        $this->unit_cost_rupees = '0';
        $this->reorder_threshold = '0';
    }

    public function render(): View
    {
        $ingredients = Ingredient::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('supplier', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.inventory-index', [
            'ingredients' => $ingredients,
            'units' => IngredientUnit::cases(),
            'lowStock' => Ingredient::query()->lowStock()->orderBy('name')->get(),
            'expiringSoon' => Ingredient::query()->expiringSoon()->orderBy('expiry_date')->get(),
        ]);
    }
}
