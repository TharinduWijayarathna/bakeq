<?php

namespace App\Contracts;

use App\Models\Order;

interface StripeGateway
{
    /**
     * @param  array{success_url: string, cancel_url: string}  $urls
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Order $order, int $amountCents, array $urls): array;

    /**
     * @return array{
     *     id: string,
     *     payment_id: string|null,
     *     order_id: int|null,
     *     amount_total: int|null,
     *     payment_status: string|null
     * }
     */
    public function retrieveCheckoutSession(string $checkoutId): array;

    /**
     * @return array{
     *     type: string,
     *     checkout_id: string|null,
     *     payment_id: string|null,
     *     order_id: int|null,
     *     amount_total: int|null,
     *     payment_status: string|null
     * }
     */
    public function parseWebhook(string $payload, string $signatureHeader): array;
}
