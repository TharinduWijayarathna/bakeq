<?php

use App\Livewire\Admin\CakeForm;
use App\Models\Cake;
use App\Models\Category;
use Livewire\Livewire;

test('admins can add a cake and set its price', function () {
    $admin = adminUser();
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(CakeForm::class)
        ->set('name', 'Ribbon Birthday Cake')
        ->set('category_id', $category->id)
        ->set('price_rupees', '6200')
        ->set('note', 'Buttercream • ribbon')
        ->set('serves', '10-12')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.cakes.index'));

    $cake = Cake::query()->where('name', 'Ribbon Birthday Cake')->first();

    expect($cake)->not->toBeNull();
    expect($cake->price)->toBe(620000);
});

test('admins can update a cake price', function () {
    $admin = adminUser();
    $cake = cake(['price' => 450000]);

    Livewire::actingAs($admin)
        ->test(CakeForm::class, ['cake' => $cake])
        ->set('price_rupees', '7800')
        ->call('save')
        ->assertHasNoErrors();

    expect($cake->fresh()->price)->toBe(780000);
});
