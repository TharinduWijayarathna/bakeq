<?php

namespace App\Actions;

use App\Models\Cake;
use App\Models\CakeDesign;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AddToCart
{
    public function handle(User $user, ?Cake $cake = null, ?CakeDesign $design = null, int $quantity = 1): CartItem
    {
        if ($cake === null && $design === null) {
            throw ValidationException::withMessages([
                'cart' => 'Choose a cake or a generated design to add to your cart.',
            ]);
        }

        if ($cake !== null) {
            $existing = CartItem::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($cake)
                ->whereNull('cake_design_id')
                ->first();

            if ($existing !== null) {
                $existing->increment('quantity', $quantity);

                return $existing->refresh();
            }

            return CartItem::query()->create([
                'user_id' => $user->id,
                'cake_id' => $cake->id,
                'quantity' => $quantity,
                'unit_price' => $cake->price,
            ]);
        }

        return CartItem::query()->create([
            'user_id' => $user->id,
            'cake_design_id' => $design->id,
            'quantity' => $quantity,
            'unit_price' => $design->estimated_price,
        ]);
    }
}
