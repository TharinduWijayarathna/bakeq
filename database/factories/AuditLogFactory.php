<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'action' => 'order.status_changed',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'confirmed'],
        ];
    }
}
