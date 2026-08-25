<?php

namespace App\Models;

use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $cake_id
 * @property int|null $cake_design_id
 * @property int $quantity
 * @property int $unit_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cake|null $cake
 * @property-read CakeDesign|null $cakeDesign
 */
#[Fillable(['user_id', 'cake_id', 'cake_design_id', 'quantity', 'unit_price'])]
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cake(): BelongsTo
    {
        return $this->belongsTo(Cake::class);
    }

    public function cakeDesign(): BelongsTo
    {
        return $this->belongsTo(CakeDesign::class);
    }

    public function lineTotal(): int
    {
        return $this->unit_price * $this->quantity;
    }

    public function displayName(): string
    {
        if ($this->cake !== null) {
            return $this->cake->name;
        }

        $selections = $this->cakeDesign?->selections ?? [];

        if (($selections['mode'] ?? null) === 'redesign' && filled($selections['cake_name'] ?? null)) {
            return 'Redesign: '.$selections['cake_name'];
        }

        if (($selections['mode'] ?? null) === 'prompt') {
            return 'AI described cake';
        }

        return 'Custom cake design';
    }
}
