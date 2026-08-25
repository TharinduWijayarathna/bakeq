<?php

namespace App\Livewire\Admin;

use App\Models\Cake;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Support\CakeCosting;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class RecipeForm extends Component
{
    public ?Recipe $recipe = null;

    public ?int $cake_id = null;

    public string $name = '';

    public string $size_label = '';

    public string $ingredient_search = '';

    /** @var list<array{ingredient_id: int|null, quantity: string}> */
    public array $lines = [];

    public function mount(?Recipe $recipe = null): void
    {
        if ($recipe?->exists) {
            $this->recipe = $recipe->load('items');
            $this->cake_id = $recipe->cake_id;
            $this->name = $recipe->name ?? '';
            $this->size_label = $recipe->size_label ?? '';
            $this->lines = $recipe->items
                ->map(fn ($item): array => [
                    'ingredient_id' => $item->ingredient_id,
                    'quantity' => (string) $item->quantity,
                ])
                ->values()
                ->all();
        }

        if ($this->lines === []) {
            $this->addLine();
        }
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'ingredient_id' => null,
            'quantity' => '0',
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);

        if ($this->lines === []) {
            $this->addLine();
        }
    }

    public function pickIngredient(int $index, int $ingredientId): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $this->lines[$index]['ingredient_id'] = $ingredientId;
        $this->ingredient_search = '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'cake_id' => ['required', 'exists:cakes,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'size_label' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $sizeLabel = trim((string) ($validated['size_label'] ?? ''));

        $duplicate = Recipe::query()
            ->where('cake_id', $validated['cake_id'])
            ->where('size_label', $sizeLabel)
            ->when($this->recipe?->exists, fn ($query) => $query->whereKeyNot($this->recipe->id))
            ->exists();

        if ($duplicate) {
            $this->addError('size_label', 'A recipe already exists for this cake and size.');

            return;
        }

        $uniqueIngredientIds = collect($validated['lines'])
            ->pluck('ingredient_id')
            ->unique()
            ->count();

        if ($uniqueIngredientIds !== count($validated['lines'])) {
            $this->addError('lines', 'Each ingredient can only appear once in a recipe.');

            return;
        }

        DB::transaction(function () use ($validated, $sizeLabel): void {
            $payload = [
                'cake_id' => $validated['cake_id'],
                'name' => $validated['name'] ?: null,
                'size_label' => $sizeLabel,
            ];

            if ($this->recipe?->exists) {
                $this->recipe->update($payload);
                $this->recipe->items()->delete();
                $recipe = $this->recipe;
            } else {
                $recipe = Recipe::query()->create($payload);
                $this->recipe = $recipe;
            }

            foreach ($validated['lines'] as $line) {
                $recipe->items()->create([
                    'ingredient_id' => $line['ingredient_id'],
                    'quantity' => $line['quantity'],
                ]);
            }
        });

        session()->flash('status', 'Recipe saved.');
        $this->redirect(route('admin.recipes.index'), navigate: true);
    }

    public function render(): View
    {
        $selectedIds = collect($this->lines)
            ->pluck('ingredient_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $ingredientsQuery = Ingredient::query()->orderBy('name');

        if ($this->ingredient_search !== '') {
            $ingredientsQuery->where('name', 'like', '%'.$this->ingredient_search.'%');
        }

        $ingredients = $ingredientsQuery->limit(40)->get();

        if ($selectedIds !== []) {
            $missing = Ingredient::query()
                ->whereIn('id', $selectedIds)
                ->whereNotIn('id', $ingredients->pluck('id'))
                ->get();

            $ingredients = $ingredients->merge($missing)->unique('id')->sortBy('name')->values();
        }

        $previewCosting = null;

        if ($this->recipe?->exists) {
            $this->recipe->load(['items.ingredient', 'cake']);
            $previewCosting = CakeCosting::forRecipe($this->recipe);
        }

        return view('livewire.admin.recipe-form', [
            'cakes' => Cake::query()->orderBy('name')->get(['id', 'name', 'size_options']),
            'ingredients' => $ingredients,
            'previewCosting' => $previewCosting,
            'formattedPreview' => $previewCosting === null ? null : [
                'cost' => Money::format($previewCosting['total_cost']),
                'sale' => Money::format($previewCosting['sale_price']),
                'profit' => Money::format($previewCosting['profit']),
                'margin' => $previewCosting['margin_percent'],
            ],
        ])->title($this->recipe?->exists ? 'Edit recipe' : 'Add recipe');
    }
}
