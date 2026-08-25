<?php

namespace App\Ai;

use App\Contracts\CakeKnowledgeAssistant;
use App\Support\Brand;
use Throwable;

class GeminiCakeKnowledgeAssistant implements CakeKnowledgeAssistant
{
    public function __construct(
        private GeminiClient $gemini,
        private DemoCakeKnowledgeAssistant $fallback,
    ) {}

    /**
     * @param  list<array{role: string, body: string}>  $history
     */
    public function reply(string $question, array $history = []): string
    {
        if (! filled(config('services.gemini.key'))) {
            return $this->fallback->reply($question, $history);
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
                    'contents' => $this->contents($question, $history),
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 220,
                    ],
                ],
                timeout: 20,
            );

            $text = $this->gemini->extractText($payload);

            return $text !== '' ? $text : $this->fallback->reply($question, $history);
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallback->reply($question, $history);
        }
    }

    /**
     * @param  list<array{role: string, body: string}>  $history
     * @return list<array{role: string, parts: list<array{text: string}>}>
     */
    private function contents(string $question, array $history): array
    {
        $contents = [];

        foreach (array_slice($history, -10) as $message) {
            $contents[] = [
                'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [
                    ['text' => $message['body']],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $question],
            ],
        ];

        return $contents;
    }

    private function systemPrompt(): string
    {
        return 'You are the '.Brand::name().' question helper for a home bakery in Sri Lanka.
Answer one cake question at a time. Write for anyone: short, plain words, no jargon, no chatty greetings.
Help with flavours, serving sizes, storage, delivery, lead times, ordering, and the visual designer.
The designer has no text prompt: customers tap cards for cake type, tiers, flavour, look, frosting, decorations, and size, then press Generate.
Prices are in Sri Lankan rupees. Custom cakes usually need a few days of lead time.
Use light Markdown so it renders cleanly: bold the key fact, then 2–5 short bullet points when a list helps. Never write a long essay.
If asked about unrelated topics, steer back to cakes in one or two sentences.
Do not invent secret discounts or promise same-day custom wedding cakes.';
    }
}
