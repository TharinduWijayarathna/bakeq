<?php

use App\Livewire\Admin\TestimonialIndex;
use App\Models\Testimonial;
use Livewire\Livewire;

test('admins can add a testimonial', function () {
    Livewire::actingAs(adminUser())
        ->test(TestimonialIndex::class)
        ->set('quote', 'Soft sponge and the flowers looked handmade.')
        ->set('author', 'Ishara M.')
        ->set('occasion', 'Birthday order')
        ->set('rating', 5)
        ->set('sort', 2)
        ->call('save')
        ->assertHasNoErrors();

    $testimonial = Testimonial::query()->where('author', 'Ishara M.')->first();

    expect($testimonial)->not->toBeNull();
    expect($testimonial->quote)->toBe('Soft sponge and the flowers looked handmade.');
    expect($testimonial->occasion)->toBe('Birthday order');
    expect($testimonial->is_active)->toBeTrue();
});

test('admins can update a testimonial', function () {
    $testimonial = Testimonial::factory()->create([
        'quote' => 'Old quote',
        'author' => 'Guest',
    ]);

    Livewire::actingAs(adminUser())
        ->test(TestimonialIndex::class)
        ->call('edit', $testimonial->id)
        ->set('quote', 'Updated review from a wedding guest.')
        ->set('author', 'Amaya K.')
        ->call('save')
        ->assertHasNoErrors();

    expect($testimonial->fresh()->quote)->toBe('Updated review from a wedding guest.');
    expect($testimonial->fresh()->author)->toBe('Amaya K.');
});

test('admins can hide a testimonial from the storefront', function () {
    $testimonial = Testimonial::factory()->create();

    Livewire::actingAs(adminUser())
        ->test(TestimonialIndex::class)
        ->call('toggle', $testimonial->id);

    expect($testimonial->fresh()->is_active)->toBeFalse();
});

test('customers cannot manage testimonials', function () {
    $this->actingAs(customer())
        ->get(route('admin.testimonials'))
        ->assertForbidden();
});
