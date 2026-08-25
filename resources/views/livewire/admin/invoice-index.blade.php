<div>
    <p class="font-script text-3xl text-primary">Billing</p>
    <h1 class="mt-1 text-4xl">Invoices</h1>
    <x-flash />

    <div class="mt-6">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search invoice # or customer…" class="w-full max-w-md rounded-2xl border border-input bg-card px-4 py-2.5 text-sm shadow-soft">
    </div>

    <div class="mt-4 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Invoice</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Issued</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr wire:key="inv-{{ $invoice->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $invoice->number }}</td>
                        <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $invoice->order) }}" class="text-primary">#{{ $invoice->order_id }}</a></td>
                        <td class="px-4 py-3">{{ $invoice->customer_snapshot['name'] ?? $invoice->order->user->name }}</td>
                        <td class="px-4 py-3">{{ $invoice->issued_at->toDayDateTimeString() }}</td>
                        <td class="px-4 py-3">{{ $invoice->formattedTotalDue() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.invoices.download', $invoice) }}" class="text-xs font-bold uppercase text-primary">Download PDF</a>
                            <a href="mailto:{{ $invoice->customer_snapshot['email'] ?? $invoice->order->user->email }}?subject={{ urlencode('Invoice '.$invoice->number) }}" class="ml-3 text-xs font-bold uppercase text-muted-foreground">Resend</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-muted-foreground">No invoices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
