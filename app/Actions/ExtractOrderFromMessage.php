<?php

namespace App\Actions;

use App\Ai\GeminiOrderMessageParser;

class ExtractOrderFromMessage
{
    public function __construct(private GeminiOrderMessageParser $parser) {}

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
    public function handle(string $message): ?array
    {
        $message = trim($message);

        if ($message === '') {
            return null;
        }

        return $this->parser->parse($message);
    }
}
