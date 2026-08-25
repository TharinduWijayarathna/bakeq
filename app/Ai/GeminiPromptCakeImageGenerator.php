<?php

namespace App\Ai;

use App\Contracts\PromptCakeImageGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GeminiPromptCakeImageGenerator implements PromptCakeImageGenerator
{
    public function __construct(
        private GeminiClient $gemini,
        private DemoPromptCakeImageGenerator $fallback,
    ) {}

    /**
     * @param  array{prompt: string, reference_path?: string|null, cake_name?: string|null, cake_description?: string|null}  $context
     */
    public function generate(array $context): string
    {
        if (! filled(config('services.gemini.key'))) {
            return $this->fallback->generate($context);
        }

        try {
            return $this->generateWithGemini($context);
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallback->generate($context);
        }
    }

    /**
     * @param  array{prompt: string, reference_path?: string|null, cake_name?: string|null, cake_description?: string|null}  $context
     */
    private function generateWithGemini(array $context): string
    {
        $parts = [];

        $reference = $this->referenceInline($context['reference_path'] ?? null);

        if ($reference !== null) {
            $parts[] = $reference;
        }

        $parts[] = ['text' => $this->buildPrompt($context)];

        $payload = $this->gemini->generateContent(
            (string) config('services.gemini.image_model'),
            [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $this->systemInstruction()],
                    ],
                ],
                'contents' => [
                    [
                        'parts' => $parts,
                    ],
                ],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE'],
                    'imageConfig' => [
                        'aspectRatio' => '1:1',
                        'imageSize' => '1K',
                    ],
                    'thinkingConfig' => [
                        'thinkingLevel' => 'minimal',
                    ],
                ],
            ],
            timeout: (int) config('services.gemini.image_timeout', 45),
            tries: 1,
        );

        $image = $this->gemini->extractImage($payload);

        if ($image === null) {
            return $this->fallback->generate($context);
        }

        $binary = base64_decode($image['data'], true);

        if ($binary === false || $binary === '') {
            return $this->fallback->generate($context);
        }

        $extension = str_contains($image['mime'], 'jpeg') || str_contains($image['mime'], 'jpg')
            ? 'jpg'
            : 'png';

        $path = 'designs/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * @param  array{prompt: string, reference_path?: string|null, cake_name?: string|null, cake_description?: string|null}  $context
     */
    private function buildPrompt(array $context): string
    {
        $lines = [
            'Photorealistic bakery product photograph of a celebration cake.',
        ];

        if (filled($context['cake_name'] ?? null)) {
            $lines[] = 'Base cake: '.$context['cake_name'].'.';
        }

        if (filled($context['cake_description'] ?? null)) {
            $lines[] = 'Base description: '.$context['cake_description'].'.';
        }

        if (filled($context['reference_path'] ?? null)) {
            $lines[] = 'Use the attached reference image as the starting look. Apply only the customer changes below.';
        }

        $lines[] = 'Customer request: '.trim($context['prompt']);
        $lines[] = 'Studio lighting, marble surface, shallow depth of field, appetizing, hand-decorated.';
        $lines[] = 'No text, letters, logos, watermark, people, or hands. Square composition.';

        return implode("\n", $lines);
    }

    private function systemInstruction(): string
    {
        return implode(' ', [
            'You generate one photorealistic bakery product photograph for Bakeq, a Sri Lankan home bakery.',
            'Follow the customer request closely.',
            'If a reference image is attached, keep the same cake identity and only apply requested changes.',
            'No text, letters, logos, watermarks, people, or hands.',
        ]);
    }

    /**
     * @return array{inlineData: array{mimeType: string, data: string}}|null
     */
    private function referenceInline(?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $binary = $disk->get($path);

        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return [
            'inlineData' => [
                'mimeType' => $mime,
                'data' => base64_encode($binary),
            ],
        ];
    }
}
