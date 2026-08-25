<?php

use App\Models\Cake;
use App\Models\Testimonial;

test('the home page loads featured cakes', function () {
    Cake::factory()->featured()->create([
        'name' => 'Classic Birthday Cake',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Classic Birthday Cake')
        ->assertSee('Rushq cakes by Shashi')
        ->assertSee('sweet cakes')
        ->assertSee('images/brand-banner.jpg', false);
});

test('the home page lazy loads below-the-fold images', function () {
    Cake::factory()->featured()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('loading="lazy"', false)
        ->assertSee('fetchpriority="high"', false)
        ->assertSee('loading="eager"', false);
});

test('the home page links social profiles and whatsapp', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(config('services.social.facebook'), false)
        ->assertSee(config('services.social.instagram'), false)
        ->assertSee(config('services.social.tiktok'), false)
        ->assertSee(config('services.social.whatsapp'), false)
        ->assertSee('Chat on WhatsApp', false);
});

test('the home page slides active testimonials', function () {
    Testimonial::factory()->create([
        'quote' => 'The cake was the highlight of our party.',
        'author' => 'Dilini P.',
        'occasion' => 'Birthday order',
        'sort' => 1,
    ]);

    Testimonial::factory()->hidden()->create([
        'quote' => 'This hidden review should stay in admin only.',
        'author' => 'Hidden Guest',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('What guests say')
        ->assertSee('The cake was the highlight of our party.')
        ->assertSee('Dilini P.')
        ->assertDontSee('This hidden review should stay in admin only.');
});
