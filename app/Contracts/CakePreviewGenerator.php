<?php

namespace App\Contracts;

interface CakePreviewGenerator
{
    /**
     * @param  list<int>  $optionIds
     */
    public function generate(array $optionIds, int $tiers): string;
}
