<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

test('guests can view the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in to Bakeq');
});

test('users can register and are signed in', function () {
    Livewire::test(Register::class)
        ->set('name', 'Nimali Perera')
        ->set('email', 'nimali@example.com')
        ->set('phone', '0712345678')
        ->set('address_line', '88 Galle Road')
        ->set('city', 'Colombo')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'nimali@example.com')->exists())->toBeTrue();
});

test('users can log in', function () {
    $user = customer(['email' => 'nimali@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'nimali@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('users cannot log in with invalid credentials', function () {
    customer(['email' => 'nimali@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'nimali@example.com')
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});
