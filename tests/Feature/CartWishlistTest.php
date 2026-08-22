<?php

use App\Livewire\CakeCatalog;
use App\Livewire\CartPage;
use App\Livewire\WishlistPage;
use App\Models\CartItem;
use App\Models\WishlistItem;
use Livewire\Livewire;

test('guests are sent to login when adding to the cart', function () {
    $cake = cake();

    Livewire::test(CakeCatalog::class)
        ->call('addToCart', $cake->id)
        ->assertRedirect(route('login'));
});

test('customers can add a cake to the cart', function () {
    $user = customer();
    $cake = cake(['name' => 'Classic Birthday Cake']);

    Livewire::actingAs($user)
        ->test(CakeCatalog::class)
        ->call('addToCart', $cake->id)
        ->assertHasNoErrors();

    expect(CartItem::query()->whereBelongsTo($user)->whereBelongsTo($cake)->exists())->toBeTrue();
});

test('customers can add a cake to the wishlist', function () {
    $user = customer();
    $cake = cake();

    Livewire::actingAs($user)
        ->test(CakeCatalog::class)
        ->call('toggleWishlist', $cake->id);

    expect(WishlistItem::query()->whereBelongsTo($user)->whereBelongsTo($cake)->exists())->toBeTrue();
});

test('customers can view and update the cart', function () {
    $user = customer();
    $item = CartItem::factory()->recycle($user)->create(['quantity' => 1]);
    $item->load('cake');

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->assertSee($item->cake->name)
        ->call('increment', $item->id);

    expect($item->fresh()->quantity)->toBe(2);
});

test('customers can view the wishlist', function () {
    $user = customer();
    $item = WishlistItem::factory()->recycle($user)->create();
    $item->load('cake');

    Livewire::actingAs($user)
        ->test(WishlistPage::class)
        ->assertSee($item->cake->name);
});
