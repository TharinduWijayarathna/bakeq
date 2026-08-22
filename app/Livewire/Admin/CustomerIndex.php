<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Customers')]
class CustomerIndex extends Component
{
    public function render(): View
    {
        return view('livewire.admin.customer-index', [
            'customers' => User::query()
                ->where('role', UserRole::Customer)
                ->withCount('orders')
                ->latest()
                ->get(),
        ]);
    }
}
