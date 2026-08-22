<?php

namespace App\Ai;

use App\Contracts\CakePreviewGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GeminiCakePreviewGenerator implements CakePreviewGenerator
{
    public function __construct(
        private GeminiClient $gemini,
        private DemoCakePreviewGenerator $fallback,
        private CakePreviewPrompt $prompt,
    ) {}

    /**
     * @param  list<int>  $optionIds
     */
    public function generate(array $optionIds, int $tiers): string
    {
        if (! filled(config('services.gemini.key'))) {
            return $this->fallback->generate($optionIds, $tiers);
        }

        try {
            return $this->generateWithGemini($optionIds, $tiers);
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallback->generate($optionIds, $tiers);
        }
    }

    /**
     * @param  list<int>  $optionIds
     */
    private function generateWithGemini(array $optionIds, int $tiers): string
    {
        $payload = $this->gemini->generateContent(
            (string) config('services.gemini.image_model'),
            [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $this->prompt->systemInstruction()],
                    ],
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $this->prompt->build($optionIds, $tiers)],
                        ],
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
            return $this->fallback->generate($optionIds, $tiers);
        }

        $binary = base64_decode($image['data'], true);

        if ($binary === false || $binary === '') {
            return $this->fallback->generate($optionIds, $tiers);
        }

        $extension = str_contains($image['mime'], 'jpeg') || str_contains($image['mime'], 'jpg')
            ? 'jpg'
            : 'png';

        $path = 'designs/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
