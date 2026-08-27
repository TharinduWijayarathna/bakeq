<?php

test('customers cannot access the admin dashboard', function () {
    $this->actingAs(customer())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admins can access the admin dashboard', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Bakery dashboard');
});

test('admin sidebar keeps store and sign out actions pinned to the viewport', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('sticky top-0', false)
        ->assertSee('h-svh', false)
        ->assertSee('min-h-0 flex-1', false)
        ->assertSee('View store')
        ->assertSee('Sign out');
});
