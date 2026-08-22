<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $cake_id
 * @property int|null $cake_design_id
 * @property string $name
 * @property int $quantity
 * @property int $unit_price
 * @property array<string, mixed>|null $selections
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['order_id', 'cake_id', 'cake_design_id', 'name', 'quantity', 'unit_price', 'selections'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'selections' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cake(): BelongsTo
    {
        return $this->belongsTo(Cake::class);
    }

    public function cakeDesign(): BelongsTo
    {
        return $this->belongsTo(CakeDesign::class);
    }

    public function formattedUnitPrice(): string
    {
        return Money::format($this->unit_price);
    }
}
