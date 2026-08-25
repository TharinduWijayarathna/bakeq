<?php

use App\Support\Brand;

test('brand config exposes the rushq identity', function () {
    expect(Brand::name())->toBe('Rushq cakes by Shashi')
        ->and(Brand::shortName())->toBe('Rushq cakes')
        ->and(Brand::tagline())->toBe('Baked with love, made for you')
        ->and(Brand::adminLabel())->toBe('Rushq Admin')
        ->and(Brand::title('Admin'))->toBe('Admin · Rushq cakes');
});

test('storefront and guest layouts use the brand name', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Rushq cakes by Shashi')
        ->assertSee('Rushq cakes', false);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in to Rushq cakes')
        ->assertSee('Rushq cakes by Shashi logo', false);
});
