<?php

namespace App\Ai;

use App\Models\DesignerOption;
use App\Support\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CakePreviewPrompt
{
    /**
     * @param  list<int>  $optionIds
     */
    public function build(array $optionIds, int $tiers): string
    {
        $options = DesignerOption::query()
            ->with('group')
            ->whereIn('id', $optionIds)
            ->get()
            ->sortBy(fn (DesignerOption $option): int => $option->group?->sort ?? 0)
            ->values();

        /** @var Collection<string, Collection<int, DesignerOption>> $bySlug */
        $bySlug = $options->groupBy(
            fn (DesignerOption $option): string => $option->group?->slug ?? 'detail',
        );

        $cakeType = $this->names($bySlug->get('cake-type', collect()));
        $isCupcakes = $cakeType->contains(
            fn (string $name): bool => Str::slug($name) === 'cupcakes',
        );

        $product = $isCupcakes
            ? 'a gift box or platter of decorated cupcakes, not a stacked tiered cake'
            : $this->tieredCake($cakeType->first() ?? 'celebration', $tiers);

        $lines = [
            'Photorealistic bakery product photograph of '.$product.'.',
            'Follow every specification below exactly. Do not add decorations, colours, toppings, or cake styles that are not listed.',
        ];

        foreach ($options as $option) {
            $lines[] = $this->specification($option);
        }

        $lines = [...$lines, ...$this->visualHints($options, $isCupcakes, $tiers)];

        $decorations = $this->names($bySlug->get('decorations', collect()));

        if ($decorations->isEmpty()) {
            $lines[] = 'No extra toppings. Do not add flowers, fruit, sprinkles, gold leaf, macarons, or writing.';
        } else {
            $lines[] = 'Include only these decorations: '.$decorations->implode(', ').'. Do not add any other toppings.';
        }

        $lines[] = 'Studio lighting, marble surface, shallow depth of field, appetizing, hand-decorated.';
        $lines[] = 'No text, letters, logos, watermark, people, or hands. Square composition.';

        return implode("\n", $lines);
    }

    public function systemInstruction(): string
    {
        return implode(' ', [
            'You generate one photorealistic bakery product photograph for '.Brand::name().', a Sri Lankan home bakery.',
            'Match the customer specifications exactly.',
            'Do not invent decorations, colours, cake types, or toppings that were not specified.',
            'No text, letters, logos, watermarks, people, or hands.',
        ]);
    }

    private function specification(DesignerOption $option): string
    {
        $group = $option->group?->name ?? 'Detail';
        $line = $group.': '.$option->name;

        if (filled($option->description)) {
            $line .= ' - '.$option->description;
        }

        if (filled($option->color_hex)) {
            $line .= ' (colour '.$option->color_hex.')';
        }

        return $line.'.';
    }

    /**
     * @param  Collection<int, DesignerOption>  $options
     * @return list<string>
     */
    private function visualHints(Collection $options, bool $isCupcakes, int $tiers): array
    {
        $hints = [];

        foreach ($options as $option) {
            $hint = $this->hintFor($option->group?->slug ?? '', $option->name);

            if ($hint !== null) {
                $hints[] = $hint;
            }
        }

        if (! $isCupcakes) {
            $hints[] = $tiers === 1
                ? 'Single-tier round cake, not multiple stacked tiers.'
                : $tiers.' distinct stacked round tiers, each smaller than the one below.';
        }

        return $hints;
    }

    private function hintFor(string $slug, string $name): ?string
    {
        $key = Str::slug($name);

        return match ($slug) {
            'cake-type' => match ($key) {
                'cupcakes' => 'Show several cupcakes with matching frosting and toppings, not a wedding or birthday tier cake.',
                'wedding' => 'Elegant formal wedding cake presentation.',
                'kids' => 'Playful kids celebration cake with bright, fun styling.',
                'birthday' => 'Festive birthday celebration cake.',
                'anniversary' => 'Elegant anniversary celebration cake.',
                default => null,
            },
            'look' => match ($key) {
                'blush-roses' => 'Covered with blush-pink buttercream or sugar roses.',
                'gold-drip' => 'Visible metallic gold ganache drip over the top edge and down the sides.',
                'white-fondant' => 'Smooth, seamless white fondant covering.',
                'naked-sponge' => 'Rustic naked cake with exposed sponge layers and filling.',
                'pastel-ombre' => 'Frosting in a soft pastel ombre fade from light to darker.',
                default => null,
            },
            'flavor', 'flavour' => match ($key) {
                'chocolate' => 'If cake layers are visible, the crumb is cocoa brown chocolate sponge.',
                'red-velvet' => 'If cake layers are visible, the crumb is deep red velvet.',
                'vanilla' => 'If cake layers are visible, the crumb is pale vanilla sponge.',
                'fruit' => 'If cake layers are visible, the sponge includes fruit pieces.',
                default => null,
            },
            default => null,
        };
    }

    private function tieredCake(string $type, int $tiers): string
    {
        $label = Str::lower($type).' cake';
        $tierLabel = $tiers === 1 ? 'a single-tier' : 'a '.$tiers.'-tier';

        return $tierLabel.' '.$label;
    }

    /**
     * @param  Collection<int, DesignerOption>  $options
     * @return Collection<int, string>
     */
    private function names(Collection $options): Collection
    {
        return $options->map(fn (DesignerOption $option): string => $option->name)->values();
    }
}
