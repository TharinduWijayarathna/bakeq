<?php

use App\Ai\CakePreviewPrompt;
use App\Enums\SelectionType;
use App\Models\DesignerOption;
use App\Models\DesignerOptionGroup;

test('the preview prompt includes colours, look, and only selected decorations', function () {
    $type = DesignerOptionGroup::factory()->create(['name' => 'Cake type', 'slug' => 'cake-type']);
    $flavour = DesignerOptionGroup::factory()->create(['name' => 'Flavour', 'slug' => 'flavour']);
    $look = DesignerOptionGroup::factory()->create(['name' => 'Look', 'slug' => 'look']);
    $decorations = DesignerOptionGroup::factory()->create([
        'name' => 'Decorations',
        'slug' => 'decorations',
        'selection_type' => SelectionType::Multiple,
        'is_required' => false,
    ]);

    $wedding = DesignerOption::factory()->create([
        'designer_option_group_id' => $type->id,
        'name' => 'Wedding',
        'description' => 'Formal tiers',
        'color_hex' => '#f8e1ea',
    ]);
    $chocolate = DesignerOption::factory()->create([
        'designer_option_group_id' => $flavour->id,
        'name' => 'Chocolate',
        'description' => 'Cocoa fudge',
        'color_hex' => '#5c3317',
    ]);
    $goldDrip = DesignerOption::factory()->create([
        'designer_option_group_id' => $look->id,
        'name' => 'Gold drip',
        'description' => 'Metallic finish',
        'color_hex' => '#c9a227',
    ]);
    $goldLeaf = DesignerOption::factory()->create([
        'designer_option_group_id' => $decorations->id,
        'name' => 'Gold leaf',
        'color_hex' => '#d4af37',
    ]);

    $prompt = app(CakePreviewPrompt::class)->build([
        $wedding->id,
        $chocolate->id,
        $goldDrip->id,
        $goldLeaf->id,
    ], 2);

    expect($prompt)
        ->toContain('a 2-tier wedding cake')
        ->toContain('colour #5c3317')
        ->toContain('Visible metallic gold ganache drip')
        ->toContain('Include only these decorations: Gold leaf')
        ->not->toContain('high detail frosting and florals')
        ->not->toContain('Do not add flowers, fruit, sprinkles, gold leaf');
});

test('cupcake selections ask for cupcakes instead of a stacked cake', function () {
    $type = DesignerOptionGroup::factory()->create(['name' => 'Cake type', 'slug' => 'cake-type']);
    $option = DesignerOption::factory()->create([
        'designer_option_group_id' => $type->id,
        'name' => 'Cupcakes',
    ]);

    $prompt = app(CakePreviewPrompt::class)->build([$option->id], 3);

    expect($prompt)
        ->toContain('cupcakes, not a stacked tiered cake')
        ->not->toContain('3-tier')
        ->not->toContain('stacked round tiers');
});

test('cakes without decorations forbid extra toppings', function () {
    $flavour = DesignerOptionGroup::factory()->create(['name' => 'Flavour', 'slug' => 'flavour']);
    $option = DesignerOption::factory()->create([
        'designer_option_group_id' => $flavour->id,
        'name' => 'Vanilla',
    ]);

    $prompt = app(CakePreviewPrompt::class)->build([$option->id], 1);

    expect($prompt)
        ->toContain('Single-tier round cake')
        ->toContain('No extra toppings')
        ->not->toContain('Include only these decorations');
});
