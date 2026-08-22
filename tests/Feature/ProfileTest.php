<?php

use App\Livewire\ProfilePage;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('customers can update their profile details', function () {
    $user = customer([
        'name' => 'Old Name',
        'city' => 'Kandy',
    ]);

    Livewire::actingAs($user)
        ->test(ProfilePage::class)
        ->set('name', 'Nimali Perera')
        ->set('phone', '0771112222')
        ->set('address_line', '12 Flower Road')
        ->set('city', 'Colombo')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nimali Perera');
    expect($user->fresh()->city)->toBe('Colombo');
});

test('customers can change their password', function () {
    $user = customer();

    Livewire::actingAs($user)
        ->test(ProfilePage::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
});
