<?php

namespace App\Livewire\Admin;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\Shift;
use App\Models\ShiftEntry;
use App\Models\User;
use App\Support\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Shifts')]
class ShiftIndex extends Component
{
    #[Url]
    public string $date = '';

    #[Url]
    public string $staff = 'all';

    public ?int $user_id = null;

    public string $starts_at_time = '08:00';

    public string $ends_at_time = '14:00';

    public string $schedule_notes = '';

    public string $clock_notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('shifts'), 403);

        if ($this->date === '') {
            $this->date = now()->toDateString();
        }
    }

    public function applyPreset(string $preset): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        [$start, $end] = match ($preset) {
            'afternoon' => ['14:00', '20:00'],
            default => ['08:00', '14:00'],
        };

        $this->starts_at_time = $start;
        $this->ends_at_time = $end;
    }

    public function createShift(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $this->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereIn('role', collect(UserRole::staffCases())->map->value->all()),
            ],
            'date' => ['required', 'date'],
            'starts_at_time' => ['required', 'date_format:H:i'],
            'ends_at_time' => ['required', 'date_format:H:i', 'after:starts_at_time'],
            'schedule_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $startsAt = Carbon::parse($validated['date'].' '.$validated['starts_at_time']);
        $endsAt = Carbon::parse($validated['date'].' '.$validated['ends_at_time']);

        $overlaps = Shift::query()
            ->where('user_id', $validated['user_id'])
            ->whereNotIn('status', [ShiftStatus::Cancelled->value, ShiftStatus::Missed->value])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($overlaps) {
            $this->addError('user_id', 'That staff member already has an overlapping shift.');

            return;
        }

        $shift = Shift::query()->create([
            'user_id' => $validated['user_id'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => ShiftStatus::Scheduled,
            'notes' => filled($validated['schedule_notes'] ?? null) ? trim($validated['schedule_notes']) : null,
        ]);

        AuditLogger::record('shift.created', $shift, null, [
            'user_id' => $shift->user_id,
            'starts_at' => $shift->starts_at->toDateTimeString(),
            'ends_at' => $shift->ends_at->toDateTimeString(),
        ]);

        $this->reset('schedule_notes');
        session()->flash('status', 'Shift scheduled for '.$shift->user->name.'.');
    }

    public function cancelShift(int $shiftId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $shift = Shift::query()->findOrFail($shiftId);

        if (! $shift->canCancel()) {
            $this->addError('board', 'Only scheduled or missed shifts can be cancelled.');

            return;
        }

        $from = $shift->status->value;
        $shift->update(['status' => ShiftStatus::Cancelled]);

        AuditLogger::record('shift.cancelled', $shift, ['status' => $from], [
            'status' => ShiftStatus::Cancelled->value,
        ]);

        session()->flash('status', 'Shift cancelled.');
    }

    public function clockIn(int $shiftId): void
    {
        abort_unless(auth()->user()?->canAccess('shifts'), 403);

        $user = auth()->user();
        $shift = Shift::query()->findOrFail($shiftId);

        if ($shift->user_id !== $user->id) {
            $this->addError('shift', 'You can only clock in to your own shift.');

            return;
        }

        if ($user->openShift() !== null) {
            $this->addError('shift', 'You already have an open shift.');

            return;
        }

        if (! $shift->canClockIn()) {
            $this->addError('shift', 'This shift cannot be clocked into.');

            return;
        }

        $entry = ShiftEntry::query()->create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'clocked_in_at' => now(),
            'notes' => filled($this->clock_notes) ? trim($this->clock_notes) : null,
        ]);

        $shift->update(['status' => ShiftStatus::InProgress]);

        AuditLogger::record('shift.clock_in', $entry, null, [
            'shift_id' => $shift->id,
            'clocked_in_at' => $entry->clocked_in_at->toDateTimeString(),
        ]);

        $this->reset('clock_notes');
        session()->flash('status', 'Clocked in for '.$shift->windowLabel().'.');
    }

    public function clockOut(): void
    {
        abort_unless(auth()->user()?->canAccess('shifts'), 403);

        $entry = auth()->user()->openShift();

        if ($entry === null) {
            $this->addError('shift', 'No open shift to clock out of.');

            return;
        }

        $entry->update(['clocked_out_at' => now()]);

        if ($entry->shift_id !== null) {
            $shift = Shift::query()->find($entry->shift_id);
            $shift?->update(['status' => ShiftStatus::Completed]);
        }

        AuditLogger::record('shift.clock_out', $entry, null, [
            'shift_id' => $entry->shift_id,
            'clocked_out_at' => $entry->clocked_out_at->toDateTimeString(),
        ]);

        session()->flash('status', 'Clocked out. Worked '.$entry->fresh()->durationLabel().'.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $canManage = $user->isAdmin();
        $day = Carbon::parse($this->date)->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        $this->markMissedShifts($day, $dayEnd);

        $boardQuery = Shift::query()
            ->with(['user', 'entries'])
            ->whereBetween('starts_at', [$day, $dayEnd])
            ->when(! $canManage, fn ($q) => $q->where('user_id', $user->id))
            ->when($canManage && $this->staff !== 'all', fn ($q) => $q->where('user_id', (int) $this->staff))
            ->orderBy('starts_at');

        $boardShifts = $boardQuery->get();

        $myActive = $user->openShift()?->load('shift');
        $myNext = Shift::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [ShiftStatus::Scheduled->value, ShiftStatus::Missed->value])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        $myCurrentScheduled = Shift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::InProgress)
            ->orderByDesc('starts_at')
            ->first();

        return view('livewire.admin.shift-index', [
            'canManage' => $canManage,
            'openEntry' => $myActive,
            'activeShift' => $myCurrentScheduled,
            'nextShift' => $myNext,
            'boardShifts' => $boardShifts,
            'staffMembers' => User::query()
                ->whereIn('role', collect(UserRole::staffCases())->map->value)
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
            'todayHours' => $this->hoursBetween(
                $canManage ? null : $user->id,
                $day,
                $dayEnd,
            ),
            'weekHours' => $this->hoursBetween(
                $user->id,
                now()->startOfWeek(),
                now()->endOfWeek(),
            ),
            'shopWeekHours' => $canManage
                ? $this->hoursBetween(null, now()->startOfWeek(), now()->endOfWeek())
                : null,
            'statuses' => ShiftStatus::cases(),
        ]);
    }

    private function markMissedShifts(CarbonInterface $day, CarbonInterface $dayEnd): void
    {
        Shift::query()
            ->whereBetween('starts_at', [$day, $dayEnd])
            ->where('status', ShiftStatus::Scheduled)
            ->where('ends_at', '<', now())
            ->whereDoesntHave('entries')
            ->update(['status' => ShiftStatus::Missed]);
    }

    private function hoursBetween(?int $userId, CarbonInterface $from, CarbonInterface $to): float
    {
        $minutes = ShiftEntry::query()
            ->whereNotNull('clocked_out_at')
            ->where('clocked_in_at', '>=', $from)
            ->where('clocked_in_at', '<=', $to)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->sum(fn (ShiftEntry $entry): int => $entry->durationMinutes());

        return round($minutes / 60, 1);
    }
}
