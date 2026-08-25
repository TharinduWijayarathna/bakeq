<?php

namespace App\Livewire\Admin;

use App\Actions\ExtractOrderFromMessage;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Order Assistant')]
class OrderAssistant extends Component
{
    public string $message = '';

    public bool $extracted = false;

    public bool $failed = false;

    public string $occasion = '';

    public string $flavor = '';

    public string $servings = '';

    public string $date = '';

    public string $time = '';

    public string $budget = '';

    public string $style_notes = '';

    public string $customer_name = '';

    public string $phone = '';

    public string $line_name = '';

    public ?int $user_id = null;

    public function extract(ExtractOrderFromMessage $extract): void
    {
        $this->validate([
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $this->failed = false;
        $this->extracted = false;

        $result = $extract->handle($this->message);

        if ($result === null) {
            $this->failed = true;
            $this->extracted = true;
            $this->line_name = 'Custom cake (from message)';
            $this->style_notes = trim($this->message);
            $this->addError('message', 'AI could not parse this message. Edit the fields below and continue to POS.');

            return;
        }

        $this->occasion = (string) ($result['occasion'] ?? '');
        $this->flavor = (string) ($result['flavor'] ?? '');
        $this->servings = (string) ($result['servings'] ?? '');
        $this->date = (string) ($result['date'] ?? '');
        $this->time = (string) ($result['time'] ?? '');
        $this->budget = (string) ($result['budget'] ?? '');
        $this->style_notes = (string) ($result['style_notes'] ?? '');
        $this->customer_name = (string) ($result['customer_name'] ?? '');
        $this->phone = (string) ($result['phone'] ?? '');
        $this->line_name = (string) ($result['line_name'] ?? 'Custom cake (from message)');
        $this->extracted = true;

        if (filled($this->customer_name) || filled($this->phone)) {
            $customer = User::query()
                ->where('role', UserRole::Customer)
                ->when(filled($this->phone), fn ($q) => $q->where('phone', $this->phone))
                ->when(blank($this->phone) && filled($this->customer_name), fn ($q) => $q->where('name', 'like', '%'.$this->customer_name.'%'))
                ->first();

            $this->user_id = $customer?->id;
        }
    }

    public function sendToPos(): void
    {
        $this->validate([
            'line_name' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', 'exists:users,id'],
            'budget' => ['nullable', 'string', 'max:50'],
            'style_notes' => ['nullable', 'string', 'max:2000'],
            'occasion' => ['nullable', 'string', 'max:120'],
            'flavor' => ['nullable', 'string', 'max:120'],
            'servings' => ['nullable', 'string', 'max:60'],
            'date' => ['nullable', 'string', 'max:40'],
            'time' => ['nullable', 'string', 'max:40'],
        ]);

        $noteParts = array_filter([
            filled($this->occasion) ? 'Occasion: '.$this->occasion : null,
            filled($this->flavor) ? 'Flavour: '.$this->flavor : null,
            filled($this->servings) ? 'Servings: '.$this->servings : null,
            filled($this->date) ? 'Date: '.$this->date : null,
            filled($this->time) ? 'Time: '.$this->time : null,
            filled($this->budget) ? 'Budget: '.$this->budget : null,
            filled($this->style_notes) ? 'Notes: '.$this->style_notes : null,
            filled($this->phone) ? 'Phone: '.$this->phone : null,
            filled($this->customer_name) ? 'Name: '.$this->customer_name : null,
        ]);

        $unitPrice = is_numeric($this->budget) ? (string) max(0, (float) $this->budget) : '0';

        session([
            'pos_prefill' => [
                'user_id' => $this->user_id,
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
        return view('livewire.admin.order-assistant', [
            'customers' => User::query()->where('role', UserRole::Customer)->orderBy('name')->get(['id', 'name', 'email', 'phone']),
        ]);
    }
}
