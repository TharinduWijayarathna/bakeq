<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\StaffPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Employees')]
class EmployeeIndex extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'baker';

    public string $password = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('employees'), 403);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canAccess('employees'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(collect(StaffPermissions::assignableRoles())->map->value->all())],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $employee = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::record('employee.created', $employee, null, [
            'name' => $employee->name,
            'role' => $employee->role->value,
        ]);

        session()->flash('status', $employee->name.' added as '.$employee->role->label().'.');
        $this->reset(['name', 'email', 'phone', 'password']);
        $this->role = 'baker';
    }

    public function updateRole(int $userId, string $role): void
    {
        abort_unless(auth()->user()?->canAccess('employees'), 403);

        $employee = User::query()->whereIn('role', collect(UserRole::staffCases())->map->value)->findOrFail($userId);

        if ($employee->id === auth()->id()) {
            $this->addError('role', 'You cannot change your own role here.');

            return;
        }

        $validatedRole = UserRole::from($role);
        $old = $employee->role->value;
        $employee->update(['role' => $validatedRole]);

        AuditLogger::record('employee.role_changed', $employee, ['role' => $old], ['role' => $validatedRole->value]);
        session()->flash('status', $employee->name.' is now '.$validatedRole->label().'.');
    }

    public function render(): View
    {
        return view('livewire.admin.employee-index', [
            'employees' => User::query()
                ->whereIn('role', collect(UserRole::staffCases())->map->value)
                ->orderBy('name')
                ->get(),
            'roles' => StaffPermissions::assignableRoles(),
        ]);
    }
}
