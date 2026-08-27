<?php

namespace App\Livewire\Admin;

use App\Actions\CreatePosOrder;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Cake;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('POS')]
class PosTerminal extends Component
{
    public ?int $user_id = null;

    public string $payment_method = 'cash';

    public string $discount_type = 'percent';

    public string $discount_value = '0';

    public string $notes = '';

    /** @var list<array{cake_id: int|null, name: string, quantity: int, unit_price_rupees: string}> */
    public array $lines = [];

    public ?int $lastOrderId = null;

    public function mount(): void
    {
        /** @var array{user_id?: int|null, notes?: string, lines?: list<array{cake_id: int|null, name: string, quantity: int, unit_price_rupees: string}>}|null $prefill */
        $prefill = session()->pull('pos_prefill');

        if (is_array($prefill)) {
            $this->user_id = isset($prefill['user_id']) ? (int) $prefill['user_id'] ?: null : null;
            $this->notes = (string) ($prefill['notes'] ?? '');
            $this->lines = $prefill['lines'] ?? [];

            if ($this->lines === []) {
                $this->addCakeLine();
            }

            session()->flash('status', 'Order Assistant details loaded. Review and complete the sale.');

            return;
        }

        $this->addCakeLine();
    }

    public function addCakeLine(): void
    {
        $this->lines[] = [
            'cake_id' => null,
            'name' => '',
            'quantity' => 1,
            'unit_price_rupees' => '0',
        ];
    }

    public function addAdhocLine(): void
    {
        $this->lines[] = [
            'cake_id' => null,
            'name' => '',
            'quantity' => 1,
            'unit_price_rupees' => '0',
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);

        if ($this->lines === []) {
            $this->addCakeLine();
        }
    }

    public function updatedLines($value, string $key): void
    {
        if (! str_ends_with($key, 'cake_id')) {
            return;
        }

        $index = (int) strtok($key, '.');
        $cakeId = $this->lines[$index]['cake_id'] ?? null;

        if (! $cakeId) {
            return;
        }

        $cake = Cake::query()->find($cakeId);

        if ($cake === null) {
            return;
        }

        $this->lines[$index]['name'] = $cake->name;
        $this->lines[$index]['unit_price_rupees'] = (string) Money::centsToRupees($cake->price);
    }

    public function checkout(CreatePosOrder $createPosOrder): void
    {
        $validated = $this->validate([
            'user_id' => ['required', 'exists:users,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.cake_id' => ['nullable', 'exists:cakes,id'],
            'lines.*.name' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'lines.*.unit_price_rupees' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = $createPosOrder->handle($validated);

        $this->lastOrderId = $order->id;
        session()->flash('status', 'POS sale #'.$order->id.' recorded. Receipt '.$order->receipt_number);
        $this->reset(['notes', 'discount_value']);
        $this->discount_value = '0';
        $this->lines = [];
        $this->addCakeLine();
    }

    public function render(): View
    {
        $lastOrder = $this->lastOrderId
            ? Order::query()->with(['items', 'user', 'invoice'])->find($this->lastOrderId)
            : null;

        return view('livewire.admin.pos-terminal', [
            'customers' => User::query()->where('role', UserRole::Customer)->orderBy('name')->get(['id', 'name', 'email']),
            'cakes' => Cake::query()->active()->orderBy('name')->get(['id', 'name', 'price']),
            'paymentMethods' => PaymentMethod::cases(),
            'discountTypes' => DiscountType::cases(),
            'lastOrder' => $lastOrder,
        ]);
    }
}
