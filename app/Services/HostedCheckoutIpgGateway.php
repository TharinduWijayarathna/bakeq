<?php

namespace App\Services;

use App\Contracts\IpgGateway;
use App\Models\Order;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Hosted checkout IPG adapter. Provider SDK usage is confined to this class.
 */
class HostedCheckoutIpgGateway implements IpgGateway
{
    public function createCheckoutSession(Order $order, int $amountCents, array $urls): array
    {
        $client = $this->client();

        if ($amountCents < 1) {
            throw new RuntimeException('Payment amount must be at least 1 cent.');
        }

        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => config('ipg.currency', 'lkr'),
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => 'Order #'.$order->id,
                    ],
                ],
            ]],
            'success_url' => $urls['success_url'],
            'cancel_url' => $urls['cancel_url'],
            'client_reference_id' => (string) $order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        return [
            'id' => (string) $session->id,
            'url' => (string) $session->url,
        ];
    }

    public function retrieveCheckoutSession(string $checkoutId): array
    {
        $session = $this->client()->checkout->sessions->retrieve($checkoutId);

        $metadata = $session->metadata ?? null;
        $orderId = is_object($metadata) ? ($metadata->order_id ?? null) : null;

        if ($orderId === null && filled($session->client_reference_id ?? null)) {
            $orderId = $session->client_reference_id;
        }

        $paymentId = $session->payment_intent ?? null;

        return [
            'id' => (string) $session->id,
            'payment_id' => is_string($paymentId) ? $paymentId : null,
            'order_id' => filled($orderId) ? (int) $orderId : null,
            'amount_total' => isset($session->amount_total) ? (int) $session->amount_total : null,
            'payment_status' => $session->payment_status ?? null,
        ];
    }

    public function parseWebhook(string $payload, string $signatureHeader): array
    {
        $secret = config('ipg.webhook_secret');

        if (! filled($secret)) {
            throw new RuntimeException('Online payment webhooks are not configured.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            throw new RuntimeException('Invalid payment webhook signature.', previous: $exception);
        }

        $object = $event->data->object ?? null;
        $metadata = is_object($object) ? ($object->metadata ?? null) : null;
        $orderId = is_object($metadata) ? ($metadata->order_id ?? null) : null;

        if ($orderId === null && is_object($object) && filled($object->client_reference_id ?? null)) {
            $orderId = $object->client_reference_id;
        }

        $paymentId = is_object($object) ? ($object->payment_intent ?? null) : null;

        return [
            'type' => (string) $event->type,
            'checkout_id' => is_object($object) ? ($object->id ?? null) : null,
            'payment_id' => is_string($paymentId) ? $paymentId : null,
            'order_id' => filled($orderId) ? (int) $orderId : null,
            'amount_total' => is_object($object) && isset($object->amount_total) ? (int) $object->amount_total : null,
            'payment_status' => is_object($object) ? ($object->payment_status ?? null) : null,
        ];
    }

    private function client(): StripeClient
    {
        $secret = config('ipg.secret_key');

        if (! filled($secret)) {
            throw new RuntimeException('Online payments are not configured.');
        }

        return new StripeClient($secret);
    }
}
