<?php

namespace App\Livewire\Admin;

use App\Actions\AdjustInventoryForOrder;
use App\Actions\GenerateInvoice;
use App\Enums\OrderStatus;
use App\Enums\ProductionStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Production')]
class ProductionBoard extends Component
{
    public function move(int $orderId, string $status, AdjustInventoryForOrder $inventory, GenerateInvoice $generateInvoice): void
    {
        $validated = validator(
            ['status' => $status],
            ['status' => ['required', Rule::enum(ProductionStatus::class)]],
        )->validate();

        $order = Order::query()
            ->with(['items.cake.recipes.items', 'user', 'items'])
            ->whereNotIn('status', [OrderStatus::Cancelled->value])
            ->findOrFail($orderId);

        $production = ProductionStatus::from($validated['status']);
        $fromProduction = $order->production_status;
        $fromStatus = $order->status;
        $toStatus = $fromStatus;
        $payload = ['production_status' => $production];

        if ($production === ProductionStatus::Delivered) {
            $toStatus = OrderStatus::Delivered;
            $payload['status'] = $toStatus;
        } elseif ($fromStatus === OrderStatus::Pending && $production !== ProductionStatus::Planning) {
            $toStatus = OrderStatus::Confirmed;
            $payload['status'] = $toStatus;
        } elseif ($production === ProductionStatus::Baking) {
            $toStatus = OrderStatus::Baking;
            $payload['status'] = $toStatus;
        }

        try {
            if ($toStatus !== $fromStatus) {
                $inventory->syncForStatusChange($order, $fromStatus, $toStatus);
            }
        } catch (InsufficientStockException $exception) {
            $this->addError('board', $exception->getMessage());

            return;
        }

        $order->update($payload);
        $generateInvoice->handle($order->fresh(['user', 'items']));

        AuditLogger::record('order.production_status_changed', $order, [
            'production_status' => $fromProduction->value,
            'status' => $fromStatus->value,
        ], [
            'production_status' => $production->value,
            'status' => $toStatus->value,
        ]);

        $this->resetErrorBag('board');
        session()->flash('status', 'Order #'.$order->id.' moved to '.$production->label().'.');
    }

    public function render(): View
    {
        $orders = Order::query()
            ->with(['user', 'items'])
            ->whereNotIn('status', [OrderStatus::Cancelled->value])
            ->latest()
            ->get();

        /** @var Collection<string, Collection<int, Order>> $columns */
        $columns = collect(ProductionStatus::cases())
            ->mapWithKeys(fn (ProductionStatus $status): array => [
                $status->value => $orders->where('production_status', $status)->values(),
            ]);

        return view('livewire.admin.production-board', [
            'columns' => $columns,
            'statuses' => ProductionStatus::cases(),
        ]);
    }
}
