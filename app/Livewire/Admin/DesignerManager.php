<?php

namespace App\Livewire\Admin;

use App\Enums\SelectionType;
use App\Models\DesignerOption;
use App\Models\DesignerOptionGroup;
use App\Models\DesignerSetting;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Designer')]
class DesignerManager extends Component
{
    public int $min_tiers = 1;

    public int $max_tiers = 3;

    public int $lead_days = 3;

    public string $notice = '';

    public string $base_price_rupees = '4500';

    public string $group_name = '';

    public string $group_selection_type = 'single';

    public bool $group_is_required = true;

    public int $group_min_select = 1;

    public int $group_max_select = 1;

    public int $group_sort = 0;

    public string $option_name = '';

    public string $option_description = '';

    public string $option_color = '#f4a6bf';

    public string $option_extra_rupees = '0';

    public ?int $option_group_id = null;

    public function mount(): void
    {
        $this->fillFromSettings();
    }

    public function saveSettings(): void
    {
        $validated = $this->validate([
            'min_tiers' => ['required', 'integer', 'min:1', 'max:10'],
            'max_tiers' => ['required', 'integer', 'min:1', 'max:10', 'gte:min_tiers'],
            'lead_days' => ['required', 'integer', 'min:0', 'max:30'],
            'notice' => ['nullable', 'string', 'max:500'],
            'base_price_rupees' => ['required', 'numeric', 'min:0'],
        ]);

        DesignerSetting::current()->update([
            'min_tiers' => $validated['min_tiers'],
            'max_tiers' => $validated['max_tiers'],
            'lead_days' => $validated['lead_days'],
            'notice' => $validated['notice'] ?? null,
            'base_price' => Money::rupeesToCents($validated['base_price_rupees']),
        ]);

        session()->flash('status', 'Designer rules saved.');
    }

    public function addGroup(): void
    {
        $validated = $this->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'group_selection_type' => ['required', 'in:single,multiple'],
            'group_is_required' => ['boolean'],
            'group_min_select' => ['required', 'integer', 'min:0'],
            'group_max_select' => ['required', 'integer', 'min:1'],
            'group_sort' => ['required', 'integer', 'min:0'],
        ]);

        DesignerOptionGroup::query()->create([
            'name' => $validated['group_name'],
            'slug' => Str::slug($validated['group_name']).'-'.Str::random(4),
            'selection_type' => SelectionType::from($validated['group_selection_type']),
            'is_required' => $this->group_is_required,
            'min_select' => $validated['group_min_select'],
            'max_select' => $validated['group_max_select'],
            'sort' => $validated['group_sort'],
            'is_active' => true,
        ]);

        $this->reset('group_name', 'group_sort');
        session()->flash('status', 'Option group added.');
    }

    public function toggleGroup(int $groupId): void
    {
        $group = DesignerOptionGroup::query()->findOrFail($groupId);
        $group->update(['is_active' => ! $group->is_active]);
    }

    public function deleteGroup(int $groupId): void
    {
        DesignerOptionGroup::query()->findOrFail($groupId)->delete();
        session()->flash('status', 'Group removed.');
    }

    public function addOption(): void
    {
        $validated = $this->validate([
            'option_group_id' => ['required', 'exists:designer_option_groups,id'],
            'option_name' => ['required', 'string', 'max:255'],
            'option_description' => ['nullable', 'string', 'max:255'],
            'option_color' => ['nullable', 'string', 'max:7'],
            'option_extra_rupees' => ['required', 'numeric', 'min:0'],
        ]);

        DesignerOption::query()->create([
            'designer_option_group_id' => $validated['option_group_id'],
            'name' => $validated['option_name'],
            'description' => $validated['option_description'] ?? null,
            'color_hex' => $validated['option_color'] ?: null,
            'extra_price' => Money::rupeesToCents($validated['option_extra_rupees']),
            'sort' => 0,
            'is_active' => true,
        ]);

        $this->reset('option_name', 'option_description', 'option_extra_rupees');
        session()->flash('status', 'Option added.');
    }

    public function deleteOption(int $optionId): void
    {
        DesignerOption::query()->findOrFail($optionId)->delete();
        session()->flash('status', 'Option removed.');
    }

    public function toggleOption(int $optionId): void
    {
        $option = DesignerOption::query()->findOrFail($optionId);
        $option->update(['is_active' => ! $option->is_active]);
    }

    public function render(): View
    {
        return view('livewire.admin.designer-manager', [
            'groups' => DesignerOptionGroup::query()->with('options')->orderBy('sort')->get(),
        ]);
    }

    private function fillFromSettings(): void
    {
        $settings = DesignerSetting::current();
        $this->min_tiers = $settings->min_tiers;
        $this->max_tiers = $settings->max_tiers;
        $this->lead_days = $settings->lead_days;
        $this->notice = $settings->notice ?? '';
        $this->base_price_rupees = (string) Money::centsToRupees($settings->base_price);
    }
}
