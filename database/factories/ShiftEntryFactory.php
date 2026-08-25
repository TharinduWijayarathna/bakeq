<?php

namespace Database\Factories;

use App\Models\ShiftEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftEntry>
 */
class ShiftEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $in = now()->subHours(fake()->numberBetween(1, 6));

        return [
            'user_id' => User::factory()->baker(),
            'clocked_in_at' => $in,
            'clocked_out_at' => null,
            'notes' => null,
        ];
    }
}
