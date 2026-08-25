<?php

namespace App\Contracts;

interface PromptCakeImageGenerator
{
    /**
     * @param  array{prompt: string, reference_path?: string|null, cake_name?: string|null, cake_description?: string|null}  $context
     */
    public function generate(array $context): string;
}
