<?php

namespace App\Livewire\Admin;

use App\Models\ShiftEntry;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Shifts')]
class ShiftIndex extends Component
{
    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('shifts'), 403);
    }

    public function clockIn(): void
    {
        abort_unless(auth()->user()?->canAccess('shifts'), 403);

        $user = auth()->user();

        if ($user->openShift() !== null) {
            $this->addError('shift', 'You already have an open shift.');

            return;
        }

        $shift = ShiftEntry::query()->create([
            'user_id' => $user->id,
            'clocked_in_at' => now(),
            'notes' => filled($this->notes) ? trim($this->notes) : null,
        ]);

        AuditLogger::record('shift.clock_in', $shift, null, [
            'clocked_in_at' => $shift->clocked_in_at->toDateTimeString(),
        ]);

        $this->reset('notes');
        session()->flash('status', 'Clocked in.');
    }

    public function clockOut(): void
    {
        abort_unless(auth()->user()?->canAccess('shifts'), 403);

        $shift = auth()->user()->openShift();

        if ($shift === null) {
            $this->addError('shift', 'No open shift to clock out of.');

            return;
        }

        $shift->update(['clocked_out_at' => now()]);

        AuditLogger::record('shift.clock_out', $shift, null, [
            'clocked_out_at' => $shift->clocked_out_at->toDateTimeString(),
        ]);

        session()->flash('status', 'Clocked out.');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.admin.shift-index', [
            'openShift' => $user->openShift(),
            'entries' => ShiftEntry::query()
                ->with('user')
                ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
                ->latest('clocked_in_at')
                ->limit(40)
                ->get(),
        ]);
    }
}
