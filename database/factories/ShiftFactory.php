<?php

namespace Database\Factories;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $starts = now()->startOfHour();

        return [
            'user_id' => User::factory()->baker(),
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHours(6),
            'status' => ShiftStatus::Scheduled,
            'notes' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::Scheduled,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::InProgress,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(5),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::Completed,
            'starts_at' => now()->subHours(7),
            'ends_at' => now()->subHour(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
