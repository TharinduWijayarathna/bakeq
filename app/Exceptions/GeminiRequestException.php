<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class GeminiRequestException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response): self
    {
        $apiMessage = $response->json('error.message') ?? $response->body();

        return new self(
            'AI request failed: '.mb_substr((string) $apiMessage, 0, 300),
            $response->status(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'status' => $this->status,
        ];
    }
}
