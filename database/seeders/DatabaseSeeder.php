<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@bakeq.test'],
            [
                'name' => 'Bakeq Admin',
                'password' => 'password',
                'phone' => '0770000000',
                'address_line' => '12 Flower Road',
                'city' => 'Colombo',
                'role' => UserRole::Admin,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@bakeq.test'],
            [
                'name' => 'Nimali Perera',
                'password' => 'password',
                'phone' => '0712345678',
                'address_line' => '88 Galle Road',
                'city' => 'Colombo',
                'role' => UserRole::Customer,
            ],
        );

        $this->call([
            CategorySeeder::class,
            CakeSeeder::class,
            DesignerSeeder::class,
            TestimonialSeeder::class,
        ]);

        ShopSetting::current();
    }
}
