<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'quote' => 'The cake was the highlight of our party: soft, fresh and exactly the design I sent.',
                'author' => 'Dilini P.',
                'occasion' => 'Birthday order',
                'rating' => 5,
                'sort' => 1,
            ],
            [
                'quote' => 'Our wedding cake looked as beautiful as it tasted. Guests kept asking which bakery we used.',
                'author' => 'Amaya & Kasun',
                'occasion' => 'Wedding cake',
                'rating' => 5,
                'sort' => 2,
            ],
            [
                'quote' => 'Cupcakes arrived chilled and perfect. The designer made it so easy to pick colours and flavours.',
                'author' => 'Nimali R.',
                'occasion' => 'Cupcake box',
                'rating' => 5,
                'sort' => 3,
            ],
            [
                'quote' => 'Ordered a last-minute anniversary cake and it still felt custom. Soft sponge, not too sweet.',
                'author' => 'Tharindu S.',
                'occasion' => 'Anniversary',
                'rating' => 5,
                'sort' => 4,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::query()->updateOrCreate(
                ['author' => $testimonial['author'], 'occasion' => $testimonial['occasion']],
                [...$testimonial, 'is_active' => true],
            );
        }
    }
}
