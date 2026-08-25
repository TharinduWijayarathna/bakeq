<?php

namespace App\Ai;

use App\Support\Brand;
use Illuminate\Support\Arr;
use Throwable;

class GeminiOrderMessageParser
{
    public function __construct(private GeminiClient $gemini) {}

    /**
     * @return array{
     *     occasion: string|null,
     *     flavor: string|null,
     *     servings: string|null,
     *     date: string|null,
     *     time: string|null,
     *     budget: string|null,
     *     style_notes: string|null,
     *     customer_name: string|null,
     *     phone: string|null,
     *     line_name: string|null
     * }|null
     */
    public function parse(string $message): ?array
    {
        if (! filled(config('services.gemini.key'))) {
            return null;
        }

        try {
            $payload = $this->gemini->generateContent(
                (string) config('services.gemini.model'),
                [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $this->systemPrompt()],
                        ],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $message],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 500,
                        'responseMimeType' => 'application/json',
                    ],
                ],
                timeout: 25,
            );

            $text = $this->gemini->extractText($payload);

            if ($text === '') {
                return null;
            }

            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($text, true);

            if (! is_array($decoded)) {
                return null;
            }

            return $this->normalize($decoded);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{
     *     occasion: string|null,
     *     flavor: string|null,
     *     servings: string|null,
     *     date: string|null,
     *     time: string|null,
     *     budget: string|null,
     *     style_notes: string|null,
     *     customer_name: string|null,
     *     phone: string|null,
     *     line_name: string|null
     * }
     */
    private function normalize(array $decoded): array
    {
        $string = fn (string $key): ?string => filled(Arr::get($decoded, $key))
            ? trim((string) Arr::get($decoded, $key))
            : null;

        $occasion = $string('occasion');
        $flavor = $string('flavor');
        $servings = $string('servings');

        $lineParts = array_filter([$occasion, $flavor, $servings ? 'serves '.$servings : null]);

        return [
            'occasion' => $occasion,
            'flavor' => $flavor,
            'servings' => $servings,
            'date' => $string('date'),
            'time' => $string('time'),
            'budget' => $string('budget'),
            'style_notes' => $string('style_notes'),
            'customer_name' => $string('customer_name'),
            'phone' => $string('phone'),
            'line_name' => $lineParts !== [] ? implode(' · ', $lineParts) : 'Custom cake (from message)',
        ];
    }

    private function systemPrompt(): string
    {
        return 'You extract cake order details from a raw customer message (WhatsApp, SMS, or email) for '.Brand::name().', a Sri Lankan bakery.
Return ONLY a JSON object with these keys:
occasion, flavor, servings, date, time, budget, style_notes, customer_name, phone
Use null for unknown fields. Keep values short and plain. Dates as YYYY-MM-DD when clear, otherwise the phrasing given. Budget as a number string without currency symbols when possible.';
    }
}
