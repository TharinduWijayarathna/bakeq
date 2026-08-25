<?php

namespace App\Support;

use App\Models\Cake;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

class AssistantTools
{
    /**
     * Try to handle a structured assistant request before falling back to Gemini.
     *
     * @return array{handled: bool, answer: string|null}
     */
    public static function tryHandle(string $message, ?User $user = null): array
    {
        $message = trim($message);

        if ($message === '') {
            return ['handled' => false, 'answer' => null];
        }

        if ($lookup = self::orderStatusLookup($message, $user)) {
            return ['handled' => true, 'answer' => $lookup];
        }

        if ($faq = self::faqAnswer($message)) {
            return ['handled' => true, 'answer' => $faq];
        }

        if ($recs = self::recommendations($message)) {
            return ['handled' => true, 'answer' => $recs];
        }

        return ['handled' => false, 'answer' => null];
    }

    public static function whatsappHandoffUrl(?string $prefill = null): string
    {
        $base = (string) config('services.social.whatsapp', 'https://wa.me/94767681678');

        if (blank($prefill)) {
            return $base;
        }

        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.'text='.rawurlencode($prefill);
    }

    private static function orderStatusLookup(string $message, ?User $user): ?string
    {
        if (! preg_match('/\b(?:order\s*#?\s*|status\s+(?:of\s+)?(?:order\s*)?#?)(\d+)\b/i', $message, $matches)
            && ! preg_match('/^#?(\d{1,8})$/', $message, $matches)) {
            return null;
        }

        $orderId = (int) $matches[1];
        $order = Order::query()->with('items')->find($orderId);

        if ($order === null) {
            return "**Order #{$orderId}** was not found. Double-check the number, or chat with us on WhatsApp.";
        }

        if ($user !== null && $user->isCustomer() && $order->user_id !== $user->id) {
            return 'That order belongs to another account. Sign in with the account that placed it, or message us on WhatsApp with the order number.';
        }

        $items = $order->items->take(3)->pluck('name')->implode(', ');
        $extra = $order->items->count() > 3 ? '…' : '';

        return implode("\n", [
            "**Order #{$order->id}** is **{$order->status->label()}**.",
            '- Production: '.$order->production_status->label(),
            '- Delivery date: '.$order->delivery_date->toFormattedDateString(),
            '- Items: '.$items.$extra,
            '- Total due: '.$order->formattedTotalDue(),
            '',
            'Need a human? Use the WhatsApp handoff below.',
        ]);
    }

    private static function faqAnswer(string $message): ?string
    {
        $normalized = Str::lower($message);

        $faqs = [
            ['needles' => ['lead time', 'how long', 'days ahead', 'how many days'], 'answer' => '**Lead time** is usually a few days for custom cakes so every layer is baked fresh. Catalog cakes list their lead time on the cake page; the designer also shows the studio lead time.'],
            ['needles' => ['store', 'storage', 'refrigerat', 'keep a cake'], 'answer' => '**Storage:** keep cream cakes refrigerated. Plain buttercream cakes can sit in a cool room for a few hours. Best enjoyed within **48 hours**.'],
            ['needles' => ['deliver', 'pickup', 'pick up'], 'answer' => '**Delivery & pickup:** choose either at checkout. Delivery has a small fee from shop settings; pickup is usually free. Share a clear address and preferred time in the notes.'],
            ['needles' => ['deposit', 'pay', 'payment'], 'answer' => '**Payment:** online orders may ask for a deposit based on shop settings. Walk-in and POS sales can be paid by cash, card, or transfer.'],
            ['needles' => ['allergen', 'nut', 'gluten'], 'answer' => '**Allergens:** each cake page lists allergens when known. Tell us about nut, dairy, gluten, or egg allergies in the order notes so the kitchen can double-check.'],
            ['needles' => ['designer', 'customise', 'customize'], 'answer' => '**Designer:** use **Studio** to tap cards for type, tiers, flavour and finish, or **Describe it** to write a free-text prompt. Generate a preview, add notes, then add to cart.'],
        ];

        foreach ($faqs as $faq) {
            foreach ($faq['needles'] as $needle) {
                if (str_contains($normalized, $needle)) {
                    return $faq['answer'];
                }
            }
        }

        return null;
    }

    private static function recommendations(string $message): ?string
    {
        $normalized = Str::lower($message);

        if (! str_contains($normalized, 'recommend')
            && ! str_contains($normalized, 'suggest')
            && ! str_contains($normalized, 'occasion')
            && ! preg_match('/\b(birthday|wedding|budget|under|for\s+\d+)/', $normalized)) {
            return null;
        }

        $budgetCents = null;

        if (preg_match('/(?:budget|under|below|around|about)\s*(?:rs\.?\s*)?(\d{3,7})/i', $message, $matches)) {
            $budgetCents = ((int) $matches[1]) * 100;
        }

        $query = Cake::query()->active()->with('category')->orderBy('price');

        if ($budgetCents !== null) {
            $query->where('price', '<=', $budgetCents);
        }

        if (str_contains($normalized, 'wedding')) {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%wedding%')
                    ->orWhere('description', 'like', '%wedding%')
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%wedding%'));
            });
        } elseif (str_contains($normalized, 'birthday')) {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%birthday%')
                    ->orWhere('description', 'like', '%birthday%')
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%birthday%'));
            });
        }

        $cakes = $query->limit(3)->get();

        if ($cakes->isEmpty()) {
            $cakes = Cake::query()->active()->orderBy('price')->limit(3)->get();
        }

        if ($cakes->isEmpty()) {
            return 'I do not have active cakes to recommend yet. Browse the catalog or message us on WhatsApp with your occasion and budget.';
        }

        $lines = ['Here are cake ideas that fit what you asked:'];

        foreach ($cakes as $cake) {
            $lines[] = "- **{$cake->name}** — {$cake->formattedPrice()} (lead {$cake->lead_days} day".($cake->lead_days === 1 ? '' : 's').')';
        }

        $lines[] = '';
        $lines[] = 'Open a cake page to redesign it, or tell me a budget like “under 8000” for tighter suggestions.';

        return implode("\n", $lines);
    }
}
