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
