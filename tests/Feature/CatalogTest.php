<?php

use App\Livewire\CakeCatalog;
use App\Models\Category;
use Livewire\Livewire;

test('the catalog lists cakes', function () {
    cake(['name' => 'Chocolate Indulgence']);

    $this->get(route('cakes.index'))
        ->assertOk()
        ->assertSee('Chocolate Indulgence')
        ->assertSee('Explore our cake collection');
});

test('the catalog lazy loads cake images and shows a loading skeleton', function () {
    cake(['name' => 'Chocolate Indulgence']);

    $this->get(route('cakes.index'))
        ->assertOk()
        ->assertSee('loading="lazy"', false)
        ->assertSee('Loading cakes', false)
        ->assertSee('wire:loading.delay', false);
});

test('customers can search cakes by name', function () {
    cake(['name' => 'Chocolate Indulgence']);
    cake(['name' => 'Wedding Tier Cake']);

    Livewire::test(CakeCatalog::class)
        ->set('search', 'Chocolate')
        ->assertSee('Chocolate Indulgence')
        ->assertDontSee('Wedding Tier Cake');
});

test('customers can filter cakes by category', function () {
    $birthday = Category::factory()->create(['name' => 'Birthday', 'slug' => 'birthday-test']);
    $wedding = Category::factory()->create(['name' => 'Wedding', 'slug' => 'wedding-test']);

    cake(['name' => 'Party Cake', 'category_id' => $birthday->id]);
    cake(['name' => 'Ceremony Cake', 'category_id' => $wedding->id]);

    Livewire::test(CakeCatalog::class)
        ->call('setCategory', 'birthday-test')
        ->assertSee('Party Cake')
        ->assertDontSee('Ceremony Cake');
});
