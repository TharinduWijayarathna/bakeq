<?php

namespace Database\Factories;

use App\Models\Shift;
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
            'shift_id' => null,
            'clocked_in_at' => $in,
            'clocked_out_at' => null,
            'notes' => null,
        ];
    }

    public function forShift(Shift $shift): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $shift->user_id,
            'shift_id' => $shift->id,
            'clocked_in_at' => $shift->starts_at,
        ]);
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes): array {
            $in = $attributes['clocked_in_at'] ?? now()->subHours(4);

            return [
                'clocked_out_at' => $in->copy()->addHours(4),
            ];
        });
    }
}
