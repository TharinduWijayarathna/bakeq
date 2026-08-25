<?php

namespace App\Ai;

use App\Contracts\PromptCakeImageGenerator;

class DemoPromptCakeImageGenerator implements PromptCakeImageGenerator
{
    /**
     * @param  array{prompt: string, reference_path?: string|null, cake_name?: string|null, cake_description?: string|null}  $context
     */
    public function generate(array $context): string
    {
        $previews = [
            'images/previews/preview-1.jpg',
            'images/previews/preview-2.jpg',
            'images/previews/preview-3.jpg',
            'images/previews/preview-4.jpg',
            'images/previews/preview-5.jpg',
            'images/previews/preview-6.jpg',
        ];

        $hash = abs(crc32(($context['prompt'] ?? '').'|'.($context['cake_name'] ?? '')));

        return $previews[$hash % count($previews)];
    }
}
