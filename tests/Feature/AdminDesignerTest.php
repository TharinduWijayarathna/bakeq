<?php

use App\Livewire\Admin\DesignerManager;
use App\Models\DesignerSetting;
use Livewire\Livewire;

test('admins can update designer tier limits', function () {
    DesignerSetting::factory()->create([
        'min_tiers' => 1,
        'max_tiers' => 3,
    ]);

    Livewire::actingAs(adminUser())
        ->test(DesignerManager::class)
        ->set('min_tiers', 2)
        ->set('max_tiers', 5)
        ->set('lead_days', 4)
        ->set('base_price_rupees', '5000')
        ->call('saveSettings')
        ->assertHasNoErrors();

    $settings = DesignerSetting::current();

    expect($settings->min_tiers)->toBe(2);
    expect($settings->max_tiers)->toBe(5);
    expect($settings->lead_days)->toBe(4);
    expect($settings->base_price)->toBe(500000);
});
