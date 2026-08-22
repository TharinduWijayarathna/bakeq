<?php

namespace Database\Seeders;

use App\Models\Cake;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CakeSeeder extends Seeder
{
    public function run(): void
    {
        $cakes = [
            [
                'slug' => 'classic-birthday-cake',
                'category' => 'birthday',
                'name' => 'Classic Birthday Cake',
                'description' => 'A celebration sponge finished with buttercream, cherries and a touch of gold leaf.',
                'note' => 'Buttercream • cherries • gold leaf',
                'price' => 450000,
                'serves' => '8-10',
                'image_path' => '/images/cakes/birthday.jpg',
                'is_featured' => true,
            ],
            [
                'slug' => 'wedding-tier-cake',
                'category' => 'wedding',
                'name' => 'Wedding Tier Cake',
                'description' => 'Elegant fondant tiers with fresh florals, made to order for your ceremony.',
                'note' => 'Fondant • fresh florals • 2 tiers',
                'price' => 1800000,
                'serves' => '30-40',
                'image_path' => '/images/cakes/wedding.jpg',
                'is_featured' => true,
            ],
            [
                'slug' => 'cupcake-party-box',
                'category' => 'cupcakes',
                'name' => 'Cupcake Party Box',
                'description' => 'A mixed box of twelve cupcakes — perfect for sharing at the table.',
                'note' => '12 pieces • mixed flavours',
                'price' => 220000,
                'serves' => '12',
                'image_path' => '/images/cakes/cupcakes.jpg',
                'is_featured' => true,
            ],
            [
                'slug' => 'chocolate-indulgence',
                'category' => 'chocolate',
                'name' => 'Chocolate Indulgence',
                'description' => 'Dark ganache, raspberries and chocolate shavings on a rich cocoa sponge.',
                'note' => 'Dark ganache • raspberries • shavings',
                'price' => 520000,
                'serves' => '10-12',
                'image_path' => '/images/cakes/chocolate.jpg',
                'is_featured' => false,
            ],
            [
                'slug' => 'fresh-fruit-cream-cake',
                'category' => 'fruit',
                'name' => 'Fresh Fruit Cream Cake',
                'description' => 'Light sponge layered with whipped cream and seasonal Sri Lankan fruit.',
                'note' => 'Seasonal fruit • whipped cream sponge',
                'price' => 480000,
                'serves' => '10-12',
                'image_path' => '/images/cakes/fruit.jpg',
                'is_featured' => false,
            ],
            [
                'slug' => 'custom-themed-cake',
                'category' => 'custom',
                'name' => 'Custom Themed Cake',
                'description' => 'Pick a theme in the designer or tell us your idea when you order.',
                'note' => 'Unicorn / theme of your choice',
                'price' => 550000,
                'serves' => '10-12',
                'image_path' => '/images/cakes/custom.jpg',
                'is_featured' => false,
            ],
        ];

        foreach ($cakes as $cake) {
            $category = Category::query()->where('slug', $cake['category'])->firstOrFail();

            Cake::query()->updateOrCreate(
                ['slug' => $cake['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $cake['name'],
                    'description' => $cake['description'],
                    'note' => $cake['note'],
                    'price' => $cake['price'],
                    'serves' => $cake['serves'],
                    'image_path' => $cake['image_path'],
                    'is_featured' => $cake['is_featured'],
                    'is_active' => true,
                ],
            );
        }
    }
}
