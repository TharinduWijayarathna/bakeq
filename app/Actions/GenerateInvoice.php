<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\ShopSetting;
use App\Support\Brand;
use Illuminate\Support\Str;

class GenerateInvoice
{
    public function handle(Order $order): Invoice
    {
        $existing = Invoice::query()->where('order_id', $order->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $order->loadMissing(['user', 'items']);
        $shop = ShopSetting::current();

        return Invoice::query()->create([
            'order_id' => $order->id,
            'number' => $this->nextNumber($order),
            'issued_at' => now(),
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'delivery_fee' => $order->delivery_fee,
            'tax_amount' => $order->tax_amount,
            'deposit_paid' => $order->deposit_paid,
            'total_due' => $order->amountDue(),
            'line_items' => $order->items->map(fn ($item): array => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->unit_price * $item->quantity,
            ])->values()->all(),
            'business_snapshot' => [
                'name' => $shop->business_name ?: Brand::name(),
                'address' => $shop->business_address,
                'phone' => $shop->business_phone,
                'email' => $shop->business_email,
            ],
            'customer_snapshot' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
                'phone' => $order->user->phone,
                'address' => $order->delivery_address,
            ],
        ]);
    }

    private function nextNumber(Order $order): string
    {
        return 'INV-'.now()->format('Ymd').'-'.Str::padLeft((string) $order->id, 5, '0');
    }
}
