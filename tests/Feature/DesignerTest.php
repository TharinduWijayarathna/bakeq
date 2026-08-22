<?php

use App\Enums\SelectionType;
use App\Livewire\CakeDesigner;
use App\Models\CakeDesign;
use App\Models\DesignerOption;
use App\Models\DesignerOptionGroup;
use App\Models\DesignerSetting;
use Livewire\Livewire;

test('the designer has selectable cards and no prompt textarea', function () {
    DesignerSetting::factory()->create();
    $group = DesignerOptionGroup::factory()->create(['name' => 'Flavour', 'is_required' => true]);
    DesignerOption::factory()->create(['designer_option_group_id' => $group->id, 'name' => 'Vanilla']);

    $this->actingAs(customer())
        ->get(route('designer'))
        ->assertOk()
        ->assertSee('Designer')
        ->assertSee('Tap the look you want')
        ->assertSee('Vanilla')
        ->assertDontSee('<textarea', false)
        ->assertSee('Generate cake');
});

test('customers can generate a demo cake preview from selected options', function () {
    $settings = DesignerSetting::factory()->create(['min_tiers' => 1, 'max_tiers' => 3, 'base_price' => 450000]);
    $group = DesignerOptionGroup::factory()->create([
        'name' => 'Flavour',
        'is_required' => true,
        'selection_type' => SelectionType::Single,
        'min_select' => 1,
        'max_select' => 1,
    ]);
    $option = DesignerOption::factory()->create([
        'designer_option_group_id' => $group->id,
        'name' => 'Chocolate',
        'extra_price' => 40000,
    ]);

    Livewire::actingAs(customer())
        ->test(CakeDesigner::class)
        ->set('tiers', $settings->min_tiers)
        ->call('selectOption', $group->id, $option->id)
        ->call('generate')
        ->assertHasNoErrors();

    expect(CakeDesign::query()->count())->toBe(1);
    expect(CakeDesign::query()->first()->preview_path)->toContain('images/previews/');
});

test('cake type cards show illustration svgs', function () {
    DesignerSetting::factory()->create();
    $group = DesignerOptionGroup::factory()->create([
        'name' => 'Cake type',
        'slug' => 'cake-type',
        'is_required' => true,
    ]);
    DesignerOption::factory()->create([
        'designer_option_group_id' => $group->id,
        'name' => 'Birthday',
    ]);

    $this->actingAs(customer())
        ->get(route('designer'))
        ->assertOk()
        ->assertSee('images/designer/types/birthday.svg', false);
});

test('required designer groups must be selected before generating', function () {
    DesignerSetting::factory()->create();
    $group = DesignerOptionGroup::factory()->create(['is_required' => true]);
    DesignerOption::factory()->create(['designer_option_group_id' => $group->id]);

    Livewire::actingAs(customer())
        ->test(CakeDesigner::class)
        ->call('generate')
        ->assertHasErrors(['selections.'.$group->id]);
});

test('guests cannot open the designer', function () {
    $this->get(route('designer'))
        ->assertRedirect(route('login'));
});
