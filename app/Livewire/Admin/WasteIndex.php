<?php

namespace App\Livewire\Admin;

use App\Enums\WasteReason;
use App\Models\Cake;
use App\Models\Ingredient;
use App\Models\WasteEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Waste log')]
class WasteIndex extends Component
{
    public string $wasted_on = '';

    public string $item_type = 'ingredient';

    public ?int $ingredient_id = null;

    public ?int $cake_id = null;

    public string $quantity = '1';

    public string $reason = 'spoilage';

    public string $notes = '';

    public function mount(): void
    {
        $this->wasted_on = now()->toDateString();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'wasted_on' => ['required', 'date'],
            'item_type' => ['required', Rule::in(['ingredient', 'cake'])],
            'ingredient_id' => [Rule::requiredIf($this->item_type === 'ingredient'), 'nullable', 'exists:ingredients,id'],
            'cake_id' => [Rule::requiredIf($this->item_type === 'cake'), 'nullable', 'exists:cakes,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', Rule::enum(WasteReason::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ingredient = $this->item_type === 'ingredient'
            ? Ingredient::query()->find($validated['ingredient_id'])
            : null;
        $cake = $this->item_type === 'cake'
            ? Cake::query()->find($validated['cake_id'])
            : null;

        $quantity = (float) $validated['quantity'];
        $cost = WasteEntry::computeCostImpact($ingredient, $cake, $quantity);

        WasteEntry::query()->create([
            'wasted_on' => $validated['wasted_on'],
            'ingredient_id' => $ingredient?->id,
            'cake_id' => $cake?->id,
            'quantity' => $quantity,
            'reason' => $validated['reason'],
            'cost_impact' => $cost,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($ingredient !== null) {
            $ingredient->update([
                'current_stock' => max(0, (float) $ingredient->current_stock - $quantity),
            ]);
        }

        $this->reset(['ingredient_id', 'cake_id', 'notes']);
        $this->quantity = '1';
        $this->reason = 'spoilage';
        $this->wasted_on = now()->toDateString();
        session()->flash('status', 'Waste entry logged.');
    }

    public function delete(int $entryId): void
    {
        WasteEntry::query()->findOrFail($entryId)->delete();
        session()->flash('status', 'Waste entry removed.');
    }

    public function render(): View
    {
        $entries = WasteEntry::query()
            ->with(['ingredient', 'cake'])
            ->latest('wasted_on')
            ->latest('id')
            ->get();

        return view('livewire.admin.waste-index', [
            'entries' => $entries,
            'totalCost' => $entries->sum('cost_impact'),
            'ingredients' => Ingredient::query()->orderBy('name')->get(['id', 'name', 'unit', 'unit_cost']),
            'cakes' => Cake::query()->orderBy('name')->get(['id', 'name', 'price']),
            'reasons' => WasteReason::cases(),
        ]);
    }
}
