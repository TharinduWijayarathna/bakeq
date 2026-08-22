<?php

namespace App\Ai;

use App\Contracts\CakePreviewGenerator;

class DemoCakePreviewGenerator implements CakePreviewGenerator
{
    /**
     * @param  list<int>  $optionIds
     */
    public function generate(array $optionIds, int $tiers): string
    {
        $previews = [
            'images/previews/preview-1.jpg',
            'images/previews/preview-2.jpg',
            'images/previews/preview-3.jpg',
            'images/previews/preview-4.jpg',
            'images/previews/preview-5.jpg',
            'images/previews/preview-6.jpg',
        ];

        $hash = abs(crc32(implode('-', [...$optionIds, $tiers])));

        return $previews[$hash % count($previews)];
    }
}
