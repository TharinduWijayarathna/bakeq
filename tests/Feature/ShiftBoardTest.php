<?php

use App\Enums\ShiftStatus;
use App\Livewire\Admin\ShiftIndex;
use App\Models\Shift;
use App\Models\User;
use App\Support\AdminAgentTools;
use Livewire\Livewire;

test('managers can schedule a shift for staff', function () {
    $manager = User::factory()->manager()->create();
    $baker = User::factory()->baker()->create();

    Livewire::actingAs($manager)
        ->test(ShiftIndex::class)
        ->set('user_id', $baker->id)
        ->set('date', now()->toDateString())
        ->set('starts_at_time', '08:00')
        ->set('ends_at_time', '14:00')
        ->set('schedule_notes', 'Morning bake')
        ->call('createShift')
        ->assertHasNoErrors();

    $shift = Shift::query()->where('user_id', $baker->id)->first();

    expect($shift)->not->toBeNull()
        ->and($shift->status)->toBe(ShiftStatus::Scheduled)
        ->and($shift->notes)->toBe('Morning bake');
});

test('bakers cannot schedule shifts', function () {
    $baker = User::factory()->baker()->create();
    $other = User::factory()->cashier()->create();

    Livewire::actingAs($baker)
        ->test(ShiftIndex::class)
        ->set('user_id', $other->id)
        ->set('date', now()->toDateString())
        ->set('starts_at_time', '08:00')
        ->set('ends_at_time', '14:00')
        ->call('createShift')
        ->assertForbidden();
});

test('staff can clock into their assigned shift and clock out', function () {
    $baker = User::factory()->baker()->create();
    $shift = Shift::factory()->forUser($baker)->scheduled()->create([
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addHours(5),
    ]);

    Livewire::actingAs($baker)
        ->test(ShiftIndex::class)
        ->call('clockIn', $shift->id)
        ->assertHasNoErrors()
        ->call('clockOut')
        ->assertHasNoErrors();

    expect($shift->fresh()->status)->toBe(ShiftStatus::Completed)
        ->and($baker->shiftEntries()->count())->toBe(1)
        ->and($baker->shiftEntries()->first()->shift_id)->toBe($shift->id)
        ->and($baker->shiftEntries()->first()->clocked_out_at)->not->toBeNull();
});

test('staff cannot clock into someone elses shift', function () {
    $baker = User::factory()->baker()->create();
    $other = User::factory()->cashier()->create();
    $shift = Shift::factory()->forUser($other)->scheduled()->create();

    Livewire::actingAs($baker)
        ->test(ShiftIndex::class)
        ->call('clockIn', $shift->id)
        ->assertHasErrors('shift');

    expect($shift->fresh()->status)->toBe(ShiftStatus::Scheduled);
});

test('overlapping shifts are rejected', function () {
    $manager = User::factory()->manager()->create();
    $baker = User::factory()->baker()->create();

    Shift::factory()->forUser($baker)->scheduled()->create([
        'starts_at' => now()->setTime(8, 0),
        'ends_at' => now()->setTime(14, 0),
    ]);

    Livewire::actingAs($manager)
        ->test(ShiftIndex::class)
        ->set('user_id', $baker->id)
        ->set('date', now()->toDateString())
        ->set('starts_at_time', '10:00')
        ->set('ends_at_time', '16:00')
        ->call('createShift')
        ->assertHasErrors('user_id');
});

test('past scheduled shifts without clock-in become missed', function () {
    $baker = User::factory()->baker()->create();
    $day = now()->toDateString();
    $shift = Shift::factory()->forUser($baker)->scheduled()->create([
        'starts_at' => $day.' 06:00:00',
        'ends_at' => $day.' 08:00:00',
    ]);

    Livewire::actingAs($baker)
        ->test(ShiftIndex::class)
        ->set('date', $day)
        ->assertSee('Missed');

    expect($shift->fresh()->status)->toBe(ShiftStatus::Missed);
});

test('admin agent can list and create shifts', function () {
    $admin = adminUser();
    $baker = User::factory()->baker()->create(['name' => 'Agent Baker']);

    Shift::factory()->forUser($baker)->inProgress()->create();

    $on = AdminAgentTools::call('list_who_is_on', [], $admin);
    expect($on['ok'])->toBeTrue()
        ->and($on['summary'])->toContain('on shift');

    $created = AdminAgentTools::call('create_shift', [
        'staff_id' => $baker->id,
        'date' => now()->addDay()->toDateString(),
        'starts_at_time' => '08:00',
        'ends_at_time' => '14:00',
        'notes' => 'Via agent',
    ], $admin);

    expect($created['ok'])->toBeTrue()
        ->and(Shift::query()->where('notes', 'Via agent')->exists())->toBeTrue();
});
