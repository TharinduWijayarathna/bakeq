<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CustomerShow extends Component
{
    public User $customer;

    public string $loyalty_notes = '';

    public function mount(User $customer): void
    {
        abort_unless(auth()->user()?->canAccess('customers'), 403);
        abort_unless($customer->role === UserRole::Customer, 404);

        $this->customer = $customer;
        $this->loyalty_notes = (string) ($customer->loyalty_notes ?? '');
    }

    public function saveNotes(): void
    {
        abort_unless(auth()->user()?->canAccess('customers'), 403);

        $validated = $this->validate([
            'loyalty_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $old = $this->customer->loyalty_notes;
        $this->customer->update([
            'loyalty_notes' => filled($validated['loyalty_notes']) ? trim($validated['loyalty_notes']) : null,
        ]);

        AuditLogger::record('customer.loyalty_notes_updated', $this->customer, [
            'loyalty_notes' => $old,
        ], [
            'loyalty_notes' => $this->customer->loyalty_notes,
        ]);

        session()->flash('status', 'Loyalty notes saved.');
    }

    public function render(): View
    {
        $orders = $this->customer->orders()
            ->with(['items.cakeDesign'])
            ->latest()
            ->get();

        $lifetimeSpend = (int) $orders
            ->reject(fn ($order) => $order->status === OrderStatus::Cancelled)
            ->sum('subtotal');

        $flavorCounts = [];

        foreach ($orders as $order) {
            if ($order->status === OrderStatus::Cancelled) {
                continue;
            }

            foreach ($order->items as $item) {
                $labels = $item->cakeDesign?->selections['labels'] ?? null;

                if (is_array($labels) && $labels !== []) {
                    foreach ($labels as $label) {
                        $name = trim((string) $label);
                        if ($name !== '') {
                            $flavorCounts[$name] = ($flavorCounts[$name] ?? 0) + $item->quantity;
                        }
                    }

                    continue;
                }

                $flavorCounts[$item->name] = ($flavorCounts[$item->name] ?? 0) + $item->quantity;
            }
        }

        arsort($flavorCounts);

        return view('livewire.admin.customer-show', [
            'orders' => $orders,
            'lifetimeSpend' => Money::format($lifetimeSpend),
            'favoriteFlavors' => collect($flavorCounts)->take(5)->all(),
        ])->title($this->customer->name);
    }
}
