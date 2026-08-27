<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\CustomerShow;
use App\Livewire\Admin\EmployeeIndex;
use App\Livewire\Admin\ShiftIndex;
use App\Livewire\CakeAssistant;
use App\Livewire\GalleryPage;
use App\Models\AuditLog;
use App\Models\GalleryPhoto;
use App\Models\Order;
use App\Models\Shift;
use App\Models\SocialPost;
use App\Models\User;
use App\Support\AssistantTools;
use App\Support\StaffPermissions;
use Livewire\Livewire;

test('staff roles gate admin pages by permission', function () {
    $cashier = User::factory()->cashier()->create();

    $this->actingAs($cashier)
        ->get(route('admin.pos'))
        ->assertOk();

    $this->actingAs($cashier)
        ->get(route('admin.employees'))
        ->assertForbidden();

    $baker = User::factory()->baker()->create();

    $this->actingAs($baker)
        ->get(route('admin.production'))
        ->assertOk();

    $this->actingAs($baker)
        ->get(route('admin.pos'))
        ->assertForbidden();
});

test('managers can create employees and staff can clock shifts', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test(EmployeeIndex::class)
        ->set('name', 'Asha Baker')
        ->set('email', 'asha@bakeq.test')
        ->set('role', UserRole::Baker->value)
        ->set('password', 'password')
        ->call('save')
        ->assertHasNoErrors();

    $employee = User::query()->where('email', 'asha@bakeq.test')->first();

    expect($employee)->not->toBeNull()
        ->and($employee->role)->toBe(UserRole::Baker)
        ->and(AuditLog::query()->where('action', 'employee.created')->exists())->toBeTrue();

    $shift = Shift::factory()->forUser($employee)->scheduled()->create([
        'starts_at' => now()->subMinutes(5),
        'ends_at' => now()->addHours(6),
    ]);

    Livewire::actingAs($employee)
        ->test(ShiftIndex::class)
        ->call('clockIn', $shift->id)
        ->assertHasNoErrors()
        ->call('clockOut')
        ->assertHasNoErrors();

    expect($employee->shiftEntries()->count())->toBe(1)
        ->and($employee->shiftEntries()->first()->clocked_out_at)->not->toBeNull()
        ->and($employee->shiftEntries()->first()->shift_id)->toBe($shift->id);
});

test('customer crm shows lifetime spend and saves loyalty notes', function () {
    $customer = customer(['name' => 'Nimal CRM']);
    Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Delivered,
        'subtotal' => 450000,
    ]);

    Livewire::actingAs(adminUser())
        ->test(CustomerShow::class, ['customer' => $customer])
        ->assertSee('Nimal CRM')
        ->assertSee('Rs.')
        ->set('loyalty_notes', 'Prefers less sugar')
        ->call('saveNotes')
        ->assertHasNoErrors();

    expect($customer->fresh()->loyalty_notes)->toBe('Prefers less sugar');
});

test('gallery page shows photos and social posts', function () {
    GalleryPhoto::factory()->create(['title' => 'Rose Tier Cake']);
    SocialPost::factory()->create(['title' => 'Studio Reel', 'platform' => 'instagram']);

    Livewire::test(GalleryPage::class)
        ->assertSee('Rose Tier Cake')
        ->call('setTab', 'social')
        ->assertSee('Studio Reel');

    $this->get(route('gallery'))->assertOk()->assertSee('Previous cakes');
});

test('assistant looks up order status and recommends cakes', function () {
    $user = customer();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Confirmed,
    ]);
    cake(['name' => 'Budget Birthday', 'price' => 600000, 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(CakeAssistant::class)
        ->set('message', 'Status of order #'.$order->id)
        ->call('ask')
        ->assertHasNoErrors()
        ->assertSee('Order #'.$order->id)
        ->assertSee('Confirmed');

    $result = AssistantTools::tryHandle('Recommend a birthday cake under 8000');

    expect($result['handled'])->toBeTrue()
        ->and($result['answer'])->toContain('Budget Birthday');
});

test('staff permissions matrix includes all expected abilities', function () {
    expect(StaffPermissions::allows(User::factory()->admin()->create(), 'audit'))->toBeTrue()
        ->and(StaffPermissions::allows(User::factory()->cashier()->create(), 'audit'))->toBeFalse()
        ->and(StaffPermissions::allows(customer(), 'dashboard'))->toBeFalse()
        ->and(StaffPermissions::allows(User::factory()->manager()->create(), 'reports'))->toBeTrue()
        ->and(StaffPermissions::routeAbility('admin.reports.index'))->toBe('reports');
});
