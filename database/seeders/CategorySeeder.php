<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Birthday', 'slug' => 'birthday', 'sort' => 1],
            ['name' => 'Wedding', 'slug' => 'wedding', 'sort' => 2],
            ['name' => 'Cupcakes', 'slug' => 'cupcakes', 'sort' => 3],
            ['name' => 'Chocolate', 'slug' => 'chocolate', 'sort' => 4],
            ['name' => 'Fruit', 'slug' => 'fruit', 'sort' => 5],
            ['name' => 'Custom', 'slug' => 'custom', 'sort' => 6],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'is_active' => true],
            );
        }
    }
}
