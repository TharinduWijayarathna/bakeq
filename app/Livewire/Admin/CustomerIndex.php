<?php

namespace App\Livewire\Admin;

use App\Actions\CreateManualCustomer;
use App\Enums\CustomerSource;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Customers')]
class CustomerIndex extends Component
{
    #[Url]
    public string $tab = 'online';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address_line = '';

    public string $city = '';

    public function mount(): void
    {
        $this->tab = in_array($this->tab, ['online', 'manual'], true) ? $this->tab : 'online';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['online', 'manual'], true) ? $tab : 'online';
        $this->resetErrorBag();
    }

    public function createManual(CreateManualCustomer $createManualCustomer): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = $createManualCustomer->handle($validated);

        session()->flash('status', $customer->name.' added as a walk-in customer.');
        $this->reset(['name', 'email', 'phone', 'address_line', 'city']);
        $this->tab = 'manual';
    }

    public function render(): View
    {
        $source = $this->tab === 'manual' ? CustomerSource::Manual : CustomerSource::Online;

        return view('livewire.admin.customer-index', [
            'customers' => User::query()
                ->where('role', UserRole::Customer)
                ->where('customer_source', $source)
                ->withCount('orders')
                ->latest()
                ->get(),
        ]);
    }
}
