<?php

use App\Livewire\Admin\AuditLogIndex;
use App\Models\AuditLog;
use App\Models\User;
use Livewire\Livewire;

test('admins see formatted audit old and new values instead of raw json', function () {
    $admin = adminUser();

    AuditLog::factory()->create([
        'user_id' => $admin->id,
        'action' => 'order.production_status_changed',
        'old_values' => [
            'production_status' => 'planning',
            'status' => 'pending',
        ],
        'new_values' => [
            'production_status' => 'baking',
            'status' => 'confirmed',
        ],
    ]);

    Livewire::actingAs($admin)
        ->test(AuditLogIndex::class)
        ->assertOk()
        ->assertSee('production status')
        ->assertSee('planning')
        ->assertSee('baking')
        ->assertSee('pending')
        ->assertSee('confirmed')
        ->assertDontSee('"old"', false)
        ->assertDontSee('"new"', false)
        ->assertDontSee('json_encode', false);
});

test('cashiers cannot open the audit log', function () {
    Livewire::actingAs(User::factory()->cashier()->create())
        ->test(AuditLogIndex::class)
        ->assertForbidden();
});
