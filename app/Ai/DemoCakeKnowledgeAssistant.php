<?php

namespace App\Ai;

use App\Contracts\CakeKnowledgeAssistant;

class DemoCakeKnowledgeAssistant implements CakeKnowledgeAssistant
{
    /**
     * @var array<string, string>
     */
    private array $knowledge = [
        'flavor' => "**Popular flavours**\n\n- Vanilla sponge\n- Chocolate fudge\n- Red velvet\n- Butter cake\n\nFruit cakes need extra lead time because they rest before decorating.",
        'flavour' => "**Popular flavours**\n\n- Vanilla sponge\n- Chocolate fudge\n- Red velvet\n- Butter cake\n\nFruit cakes need extra lead time because they rest before decorating.",
        'tier' => "**Pick tiers by the event**\n\n- 1 tier: small gathering\n- 2 tiers: birthdays\n- 3 or more: weddings\n\nThe designer only shows the tier range the bakery has enabled.",
        'size' => "**A 1kg cake serves about 8–10 people.**\n\n- 1.5kg serves about 12–15\n- Wedding tiers are priced by serving count\n- Check each cake card for “serves”",
        'serve' => "**A 1kg cake serves about 8–10 people.**\n\n- 1.5kg serves about 12–15\n- Wedding tiers are priced by serving count\n- Check each cake card for “serves”",
        'storage' => "**Keep cream cakes in the fridge** and eat them within 24–48 hours.\n\n- Fondant wedding cakes can sit in a cool room\n- Keep all cakes away from sun and humidity",
        'deliver' => "We bake the same morning and deliver chilled on your chosen date.\n\n- Custom cakes need the lead time shown in the designer (usually 3 days)",
        'order' => "**How to order**\n\n- Browse the menu or design a cake\n- Add it to your cart\n- Checkout with a delivery date and address\n\nWe confirm every order from the bakery.",
        'price' => "Catalog cakes show a starting price in Sri Lankan rupees.\n\n- Custom designs add extra for flavour, look and decorations you pick in the designer",
        'wedding' => "Wedding cakes often use fondant or fresh flowers.\n\n- Open Designer and pick **Wedding** as the cake type\n- Then choose tiers and a look\n- Order well ahead",
        'birthday' => "Birthday cakes are our most popular order.\n\n- Buttercream with cherries works well\n- A themed custom cake also works well\n- Add a message when you checkout",
        'design' => "**The designer is tap-only, no typing.**\n\n- Open Designer\n- Tap the look, flavour, frosting and decorations you want\n- Press Generate",
        'allerg' => "Tell us about nut, dairy or gluten needs in the order notes.\n\n- Our home kitchen is **not** a dedicated allergen-free space",
        'cupcake' => "Cupcake boxes come in **12 mixed pieces**.\n\n- A good pick for parties when you want several flavours on one table",
        'lead' => "**Custom cakes need a few days** so we can bake and decorate without rushing.\n\n- Catalog cakes still need advance notice for delivery dates",
    ];

    /**
     * @param  list<array{role: string, body: string}>  $history
     */
    public function reply(string $question, array $history = []): string
    {
        $normalized = mb_strtolower($question);

        foreach ($this->knowledge as $keyword => $answer) {
            if (str_contains($normalized, $keyword)) {
                return $answer;
            }
        }

        return "**I can help with cake basics.**\n\n- Flavours and serving sizes\n- Storage and delivery\n- Lead times\n- How the designer works\n\nAsk about any of those, or browse the cakes menu to start an order.";
    }
}
