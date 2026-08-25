<?php

namespace App\Livewire;

use App\Actions\AddToCart;
use App\Enums\OrderOrigin;
use App\Enums\SelectionType;
use App\Jobs\GenerateCakePreview;
use App\Jobs\GeneratePromptCakePreview;
use App\Models\CakeDesign;
use App\Models\DesignerOption;
use App\Models\DesignerOptionGroup;
use App\Models\DesignerSetting;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Designer')]
class CakeDesigner extends Component
{
    public string $mode = 'studio';

    public int $tiers = 1;

    /**
     * @var array<int|string, int|list<int>>
     */
    public array $selections = [];

    public string $prompt = '';

    public string $cartNotes = '';

    public ?int $designId = null;

    public bool $generating = false;

    public function mount(): void
    {
        $this->tiers = DesignerSetting::current()->min_tiers;
        $this->restoreLatestDesign();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['studio', 'describe'], true)) {
            return;
        }

        $this->mode = $mode;
        $this->resetErrorBag();
    }

    public function isSelected(int $groupId, int $optionId): bool
    {
        return in_array($optionId, $this->selectedForGroup($groupId), true);
    }

    public function selectOption(int $groupId, int $optionId): void
    {
        $group = DesignerOptionGroup::query()->active()->findOrFail($groupId);

        if ($group->selection_type === SelectionType::Single) {
            $this->selections[$groupId] = $optionId;

            return;
        }

        $current = Collection::make($this->selectedForGroup($groupId));

        if ($current->contains($optionId)) {
            $this->selections[$groupId] = $current->reject(fn (int $id): bool => $id === $optionId)->values()->all();

            return;
        }

        if ($current->count() >= $group->max_select) {
            return;
        }

        $this->selections[$groupId] = $current->push($optionId)->all();
    }

    public function generate(): void
    {
        $settings = DesignerSetting::current();
        $groups = $this->activeGroups();

        $this->validate([
            'tiers' => ['required', 'integer', 'min:'.$settings->min_tiers, 'max:'.$settings->max_tiers],
        ]);

        foreach ($groups as $group) {
            $selected = $this->selectedForGroup($group->id);

            if ($group->is_required && $selected === []) {
                $this->addError('selections.'.$group->id, 'Please choose '.$group->name.'.');
            }

            if (count($selected) < $group->min_select && $group->is_required) {
                $this->addError('selections.'.$group->id, 'Choose at least '.$group->min_select.' '.$group->name.'.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $optionIds = $this->selectedOptionIds();

        $design = CakeDesign::query()->create([
            'user_id' => auth()->id(),
            'selections' => [
                'mode' => 'studio',
                'origin' => OrderOrigin::AiDesigner->value,
                'tiers' => $this->tiers,
                'option_ids' => $optionIds,
                'labels' => DesignerOption::query()->whereIn('id', $optionIds)->orderBy('name')->pluck('name')->all(),
                'customer_notes' => filled($this->cartNotes) ? trim($this->cartNotes) : null,
            ],
            'tiers' => $this->tiers,
            'preview_path' => null,
            'estimated_price' => $this->estimatedCents(),
        ]);

        $this->designId = $design->id;
        $this->generating = true;
        $this->resetErrorBag('generate');

        GenerateCakePreview::dispatch($design->id);

        $this->refreshPreview();
    }

    public function generateFromPrompt(): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'min:10', 'max:1000'],
            'cartNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = DesignerSetting::current();

        $design = CakeDesign::query()->create([
            'user_id' => auth()->id(),
            'selections' => [
                'mode' => 'prompt',
                'origin' => OrderOrigin::AiDesigner->value,
                'prompt' => trim($this->prompt),
                'customer_notes' => filled($this->cartNotes) ? trim($this->cartNotes) : null,
            ],
            'tiers' => $settings->min_tiers,
            'preview_path' => null,
            'estimated_price' => $settings->base_price,
        ]);

        $this->designId = $design->id;
        $this->generating = true;
        $this->resetErrorBag('generate');

        GeneratePromptCakePreview::dispatch($design->id);

        $this->refreshPreview();
    }

    public function refreshPreview(): void
    {
        if (! $this->generating || $this->designId === null) {
            return;
        }

        $design = CakeDesign::query()->find($this->designId);

        if ($design === null || blank($design->preview_path)) {
            return;
        }

        $this->generating = false;

        if (filled(config('services.gemini.key')) && str_starts_with($design->preview_path, 'images/previews/')) {
            $this->addError('generate', 'The AI preview took too long, so this is a stand-in. Tap Generate to try again.');
        }
    }

    public function addDesignToCart(AddToCart $addToCart): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $design = CakeDesign::query()->findOrFail($this->designId);

        if (blank($design->preview_path)) {
            $this->addError('generate', 'Wait for the preview to finish baking first.');

            return;
        }

        if (filled($this->cartNotes)) {
            $selections = $design->selections ?? [];
            $selections['customer_notes'] = trim($this->cartNotes);
            $design->update(['selections' => $selections]);
        }

        $addToCart->handle(auth()->user(), design: $design);
        session()->flash('status', 'Custom cake added to your cart.');
        $this->dispatch('cart-updated');
        $this->redirect(route('cart'), navigate: true);
    }

    public function render(): View
    {
        $settings = DesignerSetting::current();
        $groups = $this->activeGroups();

        return view('livewire.cake-designer', [
            'settings' => $settings,
            'groups' => $groups,
            'design' => $this->designId ? CakeDesign::query()->find($this->designId) : null,
            'estimatedPrice' => Money::format($this->estimatedCents()),
            'tierRange' => range($settings->min_tiers, $settings->max_tiers),
            'selectedOptions' => $this->selectedOptions(),
        ]);
    }

    /**
     * @return Collection<int, DesignerOptionGroup>
     */
    private function activeGroups(): Collection
    {
        return DesignerOptionGroup::query()
            ->active()
            ->with(['options' => fn ($query) => $query->active()->orderBy('sort')])
            ->orderBy('sort')
            ->get();
    }

    /**
     * @return list<int>
     */
    private function selectedForGroup(int $groupId): array
    {
        $value = $this->selections[$groupId] ?? $this->selections[(string) $groupId] ?? null;

        if ($value === null || $value === []) {
            return [];
        }

        return is_array($value) ? array_map('intval', $value) : [(int) $value];
    }

    /**
     * @return Collection<int, DesignerOption>
     */
    private function selectedOptions(): Collection
    {
        $ids = $this->selectedOptionIds();

        if ($ids === []) {
            return collect();
        }

        return DesignerOption::query()
            ->with('group')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (DesignerOption $option): int => $option->group?->sort ?? 0)
            ->values();
    }

    /**
     * @return list<int>
     */
    private function selectedOptionIds(): array
    {
        return Collection::make($this->selections)
            ->flatMap(fn (int|array $value): array => is_array($value) ? $value : [$value])
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function estimatedCents(): int
    {
        if ($this->mode === 'describe') {
            return DesignerSetting::current()->base_price;
        }

        $extras = DesignerOption::query()
            ->whereIn('id', $this->selectedOptionIds())
            ->sum('extra_price');

        return DesignerSetting::current()->base_price + (int) $extras;
    }

    private function restoreLatestDesign(): void
    {
        $latest = CakeDesign::query()
            ->where('user_id', auth()->id())
            ->latest('id')
            ->first();

        if ($latest === null) {
            return;
        }

        $this->designId = $latest->id;
        $this->tiers = $latest->tiers;
        $this->generating = blank($latest->preview_path);

        $mode = $latest->selections['mode'] ?? 'studio';
        $this->mode = $mode === 'prompt' ? 'describe' : 'studio';
        $this->prompt = (string) ($latest->selections['prompt'] ?? '');
        $this->cartNotes = (string) ($latest->selections['customer_notes'] ?? '');
    }
}
