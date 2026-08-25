<?php

namespace App\Support;

use App\Enums\FulfillmentMethod;
use App\Models\ShopSetting;

class OrderTotals
{
    /**
     * @return array{
     *     subtotal: int,
     *     addons_total: int,
     *     delivery_fee: int,
     *     tax_amount: int,
     *     deposit_paid: int,
     *     total_due: int
     * }
     */
    public static function calculate(
        int $itemsSubtotal,
        int $addonsTotal = 0,
        FulfillmentMethod $fulfillment = FulfillmentMethod::Delivery,
        ?ShopSetting $settings = null,
    ): array {
        $settings ??= ShopSetting::current();

        $deliveryFee = $fulfillment === FulfillmentMethod::Delivery
            ? $settings->delivery_fee
            : $settings->pickup_fee;

        $taxable = $itemsSubtotal + $addonsTotal + $deliveryFee;
        $taxAmount = (int) round($taxable * ((float) $settings->tax_percent / 100));
        $gross = $taxable + $taxAmount;
        $depositPaid = (int) round($gross * ((float) $settings->deposit_percent / 100));

        return [
            'subtotal' => $itemsSubtotal,
            'addons_total' => $addonsTotal,
            'delivery_fee' => $deliveryFee,
            'tax_amount' => $taxAmount,
            'deposit_paid' => $depositPaid,
            'total_due' => max(0, $gross - $depositPaid),
        ];
    }
}
