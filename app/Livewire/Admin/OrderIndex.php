<?php

namespace App\Livewire\Admin;

use App\Actions\CreateManualOrder;
use App\Actions\ExtractOrderFromMessage;
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

    public string $ai_message = '';

    public bool $ai_extracted = false;

    public bool $ai_failed = false;

    public string $occasion = '';

    public string $flavor = '';

    public string $servings = '';

    public string $ai_date = '';

    public string $time = '';

    public string $budget = '';

    public string $style_notes = '';

    public string $customer_name = '';

    public string $phone = '';

    public string $line_name = '';

    public ?int $assistant_user_id = null;

    public function mount(): void
    {
        $this->delivery_date = now()->addDay()->toDateString();
        $this->tab = $this->normalizeTab($this->tab);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $this->normalizeTab($tab);
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

    public function extract(ExtractOrderFromMessage $extract): void
    {
        abort_unless(auth()->user()?->canAccess('order-assistant'), 403);

        $this->validate([
            'ai_message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $this->ai_failed = false;
        $this->ai_extracted = false;

        $result = $extract->handle($this->ai_message);

        if ($result === null) {
            $this->ai_failed = true;
            $this->ai_extracted = true;
            $this->line_name = 'Custom cake (from message)';
            $this->style_notes = trim($this->ai_message);
            $this->addError('ai_message', 'AI could not parse this message. Edit the fields below and continue to POS.');

            return;
        }

        $this->occasion = (string) ($result['occasion'] ?? '');
        $this->flavor = (string) ($result['flavor'] ?? '');
        $this->servings = (string) ($result['servings'] ?? '');
        $this->ai_date = (string) ($result['date'] ?? '');
        $this->time = (string) ($result['time'] ?? '');
        $this->budget = (string) ($result['budget'] ?? '');
        $this->style_notes = (string) ($result['style_notes'] ?? '');
        $this->customer_name = (string) ($result['customer_name'] ?? '');
        $this->phone = (string) ($result['phone'] ?? '');
        $this->line_name = (string) ($result['line_name'] ?? 'Custom cake (from message)');
        $this->ai_extracted = true;

        if (filled($this->customer_name) || filled($this->phone)) {
            $customer = User::query()
                ->where('role', UserRole::Customer)
                ->when(filled($this->phone), fn ($q) => $q->where('phone', $this->phone))
                ->when(blank($this->phone) && filled($this->customer_name), fn ($q) => $q->where('name', 'like', '%'.$this->customer_name.'%'))
                ->first();

            $this->assistant_user_id = $customer?->id;
        }
    }

    public function sendToPos(): void
    {
        abort_unless(auth()->user()?->canAccess('order-assistant'), 403);

        $this->validate([
            'line_name' => ['required', 'string', 'max:255'],
            'assistant_user_id' => ['nullable', 'exists:users,id'],
            'budget' => ['nullable', 'string', 'max:50'],
            'style_notes' => ['nullable', 'string', 'max:2000'],
            'occasion' => ['nullable', 'string', 'max:120'],
            'flavor' => ['nullable', 'string', 'max:120'],
            'servings' => ['nullable', 'string', 'max:60'],
            'ai_date' => ['nullable', 'string', 'max:40'],
            'time' => ['nullable', 'string', 'max:40'],
        ]);

        $noteParts = array_filter([
            filled($this->occasion) ? 'Occasion: '.$this->occasion : null,
            filled($this->flavor) ? 'Flavour: '.$this->flavor : null,
            filled($this->servings) ? 'Servings: '.$this->servings : null,
            filled($this->ai_date) ? 'Date: '.$this->ai_date : null,
            filled($this->time) ? 'Time: '.$this->time : null,
            filled($this->budget) ? 'Budget: '.$this->budget : null,
            filled($this->style_notes) ? 'Notes: '.$this->style_notes : null,
            filled($this->phone) ? 'Phone: '.$this->phone : null,
            filled($this->customer_name) ? 'Name: '.$this->customer_name : null,
        ]);

        $unitPrice = is_numeric($this->budget) ? (string) max(0, (float) $this->budget) : '0';

        session([
            'pos_prefill' => [
                'user_id' => $this->assistant_user_id,
                'notes' => implode("\n", $noteParts),
                'lines' => [
                    [
                        'cake_id' => null,
                        'name' => $this->line_name,
                        'quantity' => 1,
                        'unit_price_rupees' => $unitPrice,
                    ],
                ],
            ],
        ]);

        $this->redirect(route('admin.pos'), navigate: true);
    }

    public function render(): View
    {
        $canUseAssistant = auth()->user()?->canAccess('order-assistant') ?? false;

        if ($this->tab === 'ai' && ! $canUseAssistant) {
            $this->tab = 'online';
        }

        $source = $this->tab === 'manual' ? OrderSource::Manual : OrderSource::Online;

        $orders = $this->tab === 'ai'
            ? collect()
            : Order::query()
                ->with('user')
                ->where('order_source', $source)
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->latest()
                ->get();

        return view('livewire.admin.order-index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'canUseAssistant' => $canUseAssistant,
            'customers' => User::query()
                ->where('role', UserRole::Customer)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone']),
            'cakes' => Cake::query()->active()->orderBy('name')->get(['id', 'name', 'price']),
            'fulfillmentMethods' => FulfillmentMethod::cases(),
        ]);
    }

    private function normalizeTab(string $tab): string
    {
        $allowed = ['online', 'manual', 'ai'];

        return in_array($tab, $allowed, true) ? $tab : 'online';
    }
}
