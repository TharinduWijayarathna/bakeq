<?php

namespace App\Livewire\Admin;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Invoices')]
class InvoiceIndex extends Component
{
    #[Url]
    public string $search = '';

    public function render(): View
    {
        $invoices = Invoice::query()
            ->with(['order.user'])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('order.user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->latest('issued_at')
            ->get();

        return view('livewire.admin.invoice-index', [
            'invoices' => $invoices,
        ]);
    }
}
