<div>
    <p class="font-script text-3xl text-primary">Team</p>
    <h1 class="mt-1 text-4xl">Employees</h1>
    <x-flash />

    <form wire:submit="save" class="mt-8 grid gap-4 rounded-4xl bg-card p-6 shadow-soft sm:grid-cols-2">
        <h2 class="text-xl font-bold sm:col-span-2">Add staff account</h2>
        <div>
            <label class="mb-1 block text-sm font-semibold">Name</label>
            <input type="text" wire:model="name" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Email</label>
            <input type="email" wire:model="email" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('email') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Phone</label>
            <input type="text" wire:model="phone" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Role</label>
            <select wire:model="role" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @foreach ($roles as $staffRole)
                    <option value="{{ $staffRole->value }}">{{ $staffRole->label() }}</option>
                @endforeach
            </select>
            @error('role') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-semibold">Temporary password</label>
            <input type="password" wire:model="password" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('password') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Create employee</button>
        </div>
    </form>

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $employee)
                    <tr wire:key="emp-{{ $employee->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $employee->name }}</td>
                        <td class="px-4 py-3">{{ $employee->email }}</td>
                        <td class="px-4 py-3">
                            <select
                                wire:change="updateRole({{ $employee->id }}, $event.target.value)"
                                class="rounded-xl border border-input bg-background px-3 py-2 text-sm"
                                @disabled($employee->id === auth()->id())
                            >
                                @foreach ($roles as $staffRole)
                                    <option value="{{ $staffRole->value }}" @selected($employee->role === $staffRole)>{{ $staffRole->label() }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
