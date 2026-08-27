<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-script text-3xl text-primary">Attendance</p>
            <h1 class="mt-1 text-4xl">Shifts</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                Managers schedule bakery slots. Staff clock in and out against their assigned shift.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label class="sr-only" for="shift-date">Date</label>
            <input
                id="shift-date"
                type="date"
                wire:model.live="date"
                class="rounded-md border border-input bg-card px-3 py-2 text-sm"
            >
            @if ($canManage)
                <select wire:model.live="staff" class="rounded-md border border-input bg-card px-3 py-2 text-sm">
                    <option value="all">All staff</option>
                    @foreach ($staffMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <x-flash />
    @error('shift') <p class="mt-3 text-sm text-destructive">{{ $message }}</p> @enderror
    @error('board') <p class="mt-3 text-sm text-destructive">{{ $message }}</p> @enderror

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-admin.stat label="Today hours" :value="$todayHours.'h'" :hint="$canManage ? 'Whole shop' : 'Your hours'" icon="clipboard" />
        <x-admin.stat label="Your week" :value="$weekHours.'h'" hint="This week" icon="users" />
        @if ($canManage)
            <x-admin.stat label="Shop week" :value="$shopWeekHours.'h'" hint="All staff" icon="layers" />
        @endif
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-lg font-bold">My shift</h2>

            @if ($openEntry)
                <p class="mt-3 text-sm text-muted-foreground">
                    On shift since <span class="font-semibold text-foreground">{{ $openEntry->clocked_in_at->format('g:i A') }}</span>
                    · {{ $openEntry->durationLabel() }} so far
                </p>
                @if ($activeShift)
                    <p class="mt-1 text-sm text-muted-foreground">Scheduled window {{ $activeShift->windowLabel() }}</p>
                @endif
                <button
                    type="button"
                    wire:click="clockOut"
                    class="mt-5 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground"
                >
                    Clock out
                </button>
            @elseif ($nextShift)
                <p class="mt-3 text-sm text-muted-foreground">
                    Next: <span class="font-semibold text-foreground">{{ $nextShift->starts_at->format('D M j') }}</span>
                    · {{ $nextShift->windowLabel() }}
                    <span class="ml-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $nextShift->status->badgeClasses() }}">{{ $nextShift->status->label() }}</span>
                </p>
                @if ($nextShift->notes)
                    <p class="mt-2 text-sm text-muted-foreground">{{ $nextShift->notes }}</p>
                @endif
                <label class="mt-4 mb-1 block text-xs font-bold uppercase tracking-wider">Notes (optional)</label>
                <input
                    type="text"
                    wire:model="clock_notes"
                    class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm"
                    placeholder="Morning bake…"
                >
                <button
                    type="button"
                    wire:click="clockIn({{ $nextShift->id }})"
                    class="mt-4 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground"
                >
                    Clock in
                </button>
            @else
                <p class="mt-4 text-sm text-muted-foreground">No upcoming shift assigned. Ask a manager to schedule you.</p>
            @endif
        </section>

        @if ($canManage)
            <section class="rounded-4xl bg-card p-6 shadow-soft">
                <h2 class="text-lg font-bold">Schedule a shift</h2>
                <form wire:submit="createShift" class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider">Staff</label>
                        <select wire:model="user_id" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                            <option value="">Choose staff…</option>
                            @foreach ($staffMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} · {{ $member->role->label() }}</option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider">Start</label>
                        <input type="time" wire:model="starts_at_time" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        @error('starts_at_time') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider">End</label>
                        <input type="time" wire:model="ends_at_time" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        @error('ends_at_time') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 flex flex-wrap gap-2">
                        <button type="button" wire:click="applyPreset('morning')" class="rounded-md bg-secondary px-3 py-1.5 text-xs font-semibold text-secondary-foreground">Morning 8–2</button>
                        <button type="button" wire:click="applyPreset('afternoon')" class="rounded-md bg-secondary px-3 py-1.5 text-xs font-semibold text-secondary-foreground">Afternoon 2–8</button>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider">Notes</label>
                        <input type="text" wire:model="schedule_notes" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm" placeholder="Bake + decorate…">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Add to schedule</button>
                    </div>
                </form>
            </section>
        @endif
    </div>

    <section class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <h2 class="text-lg font-bold">Board · {{ \Illuminate\Support\Carbon::parse($date)->format('D, M j') }}</h2>
            <p class="text-xs text-muted-foreground">{{ $boardShifts->count() }} shift{{ $boardShifts->count() === 1 ? '' : 's' }}</p>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Staff</th>
                    <th class="px-4 py-3">Window</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Worked</th>
                    <th class="px-4 py-3">Notes</th>
                    @if ($canManage)
                        <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($boardShifts as $shift)
                    <tr wire:key="board-shift-{{ $shift->id }}" class="border-t border-border">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $shift->user->name }}</p>
                            <p class="text-xs text-muted-foreground">{{ $shift->user->role->label() }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $shift->windowLabel() }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $shift->status->badgeClasses() }}">
                                {{ $shift->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $worked = $shift->workedMinutes();
                                $open = $shift->openEntry();
                            @endphp
                            @if ($open)
                                {{ $open->durationLabel() }} (live)
                            @elseif ($worked > 0)
                                {{ intdiv($worked, 60) }}h {{ $worked % 60 }}m
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $shift->notes ?? '-' }}</td>
                        @if ($canManage)
                            <td class="px-4 py-3 text-right">
                                @if ($shift->canCancel())
                                    <button
                                        type="button"
                                        wire:click="cancelShift({{ $shift->id }})"
                                        wire:confirm="Cancel this shift?"
                                        class="text-xs font-bold uppercase tracking-wider text-destructive"
                                    >
                                        Cancel
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-8 text-center text-muted-foreground">
                            No shifts scheduled for this day.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
