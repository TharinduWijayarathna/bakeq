<?php

namespace Database\Seeders;

use App\Enums\SelectionType;
use App\Models\DesignerOption;
use App\Models\DesignerOptionGroup;
use App\Models\DesignerSetting;
use Illuminate\Database\Seeder;

class DesignerSeeder extends Seeder
{
    public function run(): void
    {
        DesignerSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'min_tiers' => 1,
                'max_tiers' => 3,
                'lead_days' => 3,
                'notice' => 'We take custom cakes at least 3 days ahead so every layer is baked fresh.',
                'base_price' => 450000,
            ],
        );

        $groups = [
            [
                'slug' => 'cake-type',
                'name' => 'Cake type',
                'required' => true,
                'selection_type' => SelectionType::Single,
                'sort' => 1,
                'options' => [
                    ['name' => 'Birthday', 'description' => 'Celebration sponge', 'color_hex' => '#f4a6bf', 'extra_price' => 0, 'image_path' => '/images/designer/types/birthday.svg'],
                    ['name' => 'Wedding', 'description' => 'Formal tiers', 'color_hex' => '#f8e1ea', 'extra_price' => 800000, 'image_path' => '/images/designer/types/wedding.svg'],
                    ['name' => 'Anniversary', 'description' => 'Elegant finish', 'color_hex' => '#e8c4d4', 'extra_price' => 200000, 'image_path' => '/images/designer/types/anniversary.svg'],
                    ['name' => 'Kids', 'description' => 'Playful themed', 'color_hex' => '#7dd3c0', 'extra_price' => 50000, 'image_path' => '/images/designer/types/kids.svg'],
                    ['name' => 'Cupcakes', 'description' => 'Shareable box', 'color_hex' => '#f7c59f', 'extra_price' => 0, 'image_path' => '/images/designer/types/cupcakes.svg'],
                ],
            ],
            [
                'slug' => 'flavor',
                'name' => 'Flavour',
                'required' => true,
                'selection_type' => SelectionType::Single,
                'sort' => 2,
                'options' => [
                    ['name' => 'Vanilla', 'description' => 'Classic sponge', 'color_hex' => '#fff6e5', 'extra_price' => 0],
                    ['name' => 'Chocolate', 'description' => 'Cocoa fudge', 'color_hex' => '#5c3317', 'extra_price' => 40000],
                    ['name' => 'Red velvet', 'description' => 'Tangy cocoa', 'color_hex' => '#b42318', 'extra_price' => 60000],
                    ['name' => 'Butter cake', 'description' => 'Rich and soft', 'color_hex' => '#f3d39b', 'extra_price' => 20000],
                    ['name' => 'Fruit', 'description' => 'Seasonal fruit', 'color_hex' => '#e85d4c', 'extra_price' => 80000],
                ],
            ],
            [
                'slug' => 'look',
                'name' => 'Look',
                'required' => true,
                'selection_type' => SelectionType::Single,
                'sort' => 3,
                'options' => [
                    ['name' => 'Blush roses', 'description' => 'Pink florals', 'color_hex' => '#e11d74', 'extra_price' => 0],
                    ['name' => 'Gold drip', 'description' => 'Metallic finish', 'color_hex' => '#c9a227', 'extra_price' => 75000],
                    ['name' => 'White fondant', 'description' => 'Smooth classic', 'color_hex' => '#f7f2f4', 'extra_price' => 50000],
                    ['name' => 'Naked sponge', 'description' => 'Rustic layers', 'color_hex' => '#d4a574', 'extra_price' => 0],
                    ['name' => 'Pastel ombre', 'description' => 'Soft fade', 'color_hex' => '#cbb2fe', 'extra_price' => 40000],
                ],
            ],
            [
                'slug' => 'frosting',
                'name' => 'Frosting',
                'required' => true,
                'selection_type' => SelectionType::Single,
                'sort' => 4,
                'options' => [
                    ['name' => 'Buttercream', 'description' => 'Silky swirl', 'color_hex' => '#ffe8cc', 'extra_price' => 0],
                    ['name' => 'Fondant', 'description' => 'Smooth cover', 'color_hex' => '#eee4e1', 'extra_price' => 90000],
                    ['name' => 'Whipped cream', 'description' => 'Light finish', 'color_hex' => '#fffaf4', 'extra_price' => 20000],
                    ['name' => 'Ganache', 'description' => 'Chocolate shine', 'color_hex' => '#3d2314', 'extra_price' => 60000],
                ],
            ],
            [
                'slug' => 'decorations',
                'name' => 'Decorations',
                'required' => false,
                'selection_type' => SelectionType::Multiple,
                'min_select' => 0,
                'max_select' => 3,
                'sort' => 5,
                'options' => [
                    ['name' => 'Fresh florals', 'description' => 'Seasonal blooms', 'color_hex' => '#f4a6bf', 'extra_price' => 120000],
                    ['name' => 'Gold leaf', 'description' => 'Edible shimmer', 'color_hex' => '#d4af37', 'extra_price' => 80000],
                    ['name' => 'Cherries', 'description' => 'Classic topping', 'color_hex' => '#c81e3a', 'extra_price' => 20000],
                    ['name' => 'Macarons', 'description' => 'Petit fours', 'color_hex' => '#f9c5d1', 'extra_price' => 90000],
                    ['name' => 'Sprinkles', 'description' => 'Party finish', 'color_hex' => '#7dd3c0', 'extra_price' => 15000],
                ],
            ],
            [
                'slug' => 'size',
                'name' => 'Size',
                'required' => true,
                'selection_type' => SelectionType::Single,
                'sort' => 6,
                'options' => [
                    ['name' => '1 kg', 'description' => 'Serves 8–10', 'color_hex' => '#f4a6bf', 'extra_price' => 0],
                    ['name' => '1.5 kg', 'description' => 'Serves 12–15', 'color_hex' => '#e11d74', 'extra_price' => 150000],
                    ['name' => '2 kg', 'description' => 'Serves 18–22', 'color_hex' => '#9d174d', 'extra_price' => 300000],
                ],
            ],
        ];

        foreach ($groups as $groupData) {
            $group = DesignerOptionGroup::query()->updateOrCreate(
                ['slug' => $groupData['slug']],
                [
                    'name' => $groupData['name'],
                    'selection_type' => $groupData['selection_type'],
                    'is_required' => $groupData['required'],
                    'min_select' => $groupData['min_select'] ?? 1,
                    'max_select' => $groupData['max_select'] ?? 1,
                    'sort' => $groupData['sort'],
                    'is_active' => true,
                ],
            );

            foreach ($groupData['options'] as $index => $option) {
                DesignerOption::query()->updateOrCreate(
                    [
                        'designer_option_group_id' => $group->id,
                        'name' => $option['name'],
                    ],
                    [
                        'description' => $option['description'],
                        'color_hex' => $option['color_hex'],
                        'extra_price' => $option['extra_price'],
                        'image_path' => $option['image_path'] ?? null,
                        'sort' => $index + 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
