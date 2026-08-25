<?php

namespace App\Livewire\Admin;

use App\Actions\CreateManualOrder;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Cake;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Orders')]
class OrderIndex extends Component
{
    #[Url]
    public string $tab = 'online';

    #[Url]
    public string $status = '';

    public ?int $user_id = null;

    public ?int $cake_id = null;

    public int $quantity = 1;

    public string $delivery_date = '';

    public string $delivery_address = '';

    public string $notes = '';

    public string $fulfillment_method = 'pickup';

    public function mount(): void
    {
        $this->delivery_date = now()->addDay()->toDateString();
        $this->tab = in_array($this->tab, ['online', 'manual'], true) ? $this->tab : 'online';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['online', 'manual'], true) ? $tab : 'online';
        $this->resetErrorBag();
    }

    public function createWalkIn(CreateManualOrder $createManualOrder): void
    {
        $validated = $this->validate([
            'user_id' => ['required', 'exists:users,id'],
            'cake_id' => ['required', 'exists:cakes,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
        ]);

        $order = $createManualOrder->handle([
            ...$validated,
            'origin' => OrderOrigin::Catalog->value,
        ]);

        session()->flash('status', 'Walk-in order #'.$order->id.' created.');
        $this->reset(['cake_id', 'quantity', 'notes']);
        $this->quantity = 1;
        $this->tab = 'manual';
    }

    public function render(): View
    {
        $source = $this->tab === 'manual' ? OrderSource::Manual : OrderSource::Online;

        $orders = Order::query()
            ->with('user')
            ->where('order_source', $source)
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->get();

        return view('livewire.admin.order-index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'customers' => User::query()
                ->where('role', UserRole::Customer)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'cakes' => Cake::query()->active()->orderBy('name')->get(['id', 'name', 'price']),
            'fulfillmentMethods' => FulfillmentMethod::cases(),
        ]);
    }
}
