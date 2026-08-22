<?php

namespace App\Ai;

use App\Exceptions\GeminiRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function generateContent(string $model, array $payload, int $timeout = 20, int $tries = 2): array
    {
        $this->allowRequestToFinish($timeout);

        $response = $this->request($timeout, $tries)
            ->post('models/'.$model.':generateContent', $payload);

        if ($response->failed()) {
            throw GeminiRequestException::fromResponse($response);
        }

        /** @var array<string, mixed> $json */
        $json = $response->json();

        return $json;
    }

    private function request(int $timeout, int $tries): PendingRequest
    {
        $request = Http::baseUrl((string) config('services.gemini.base_url'))
            ->withHeaders([
                'x-goog-api-key' => (string) config('services.gemini.key'),
            ])
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->connectTimeout(15)
            ->withOptions([
                'version' => '1.1',
            ]);

        if ($tries <= 1) {
            return $request;
        }

        return $request->retry($tries, 500, function (Throwable $exception): bool {
            return $exception instanceof ConnectionException;
        }, throw: false);
    }

    private function allowRequestToFinish(int $timeout): void
    {
        $limit = $timeout + 15;
        $current = (int) ini_get('max_execution_time');

        if ($current !== 0 && $current < $limit) {
            set_time_limit($limit);
        }
    }

    public function extractText(array $payload): string
    {
        $parts = Arr::get($payload, 'candidates.0.content.parts', []);

        $text = collect($parts)
            ->map(fn (mixed $part): string => is_array($part) ? (string) ($part['text'] ?? '') : '')
            ->filter()
            ->implode("\n");

        return trim($text);
    }

    /**
     * @return array{mime: string, data: string}|null
     */
    public function extractImage(array $payload): ?array
    {
        $parts = Arr::get($payload, 'candidates.0.content.parts', []);

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;

            if (! is_array($inline) || blank($inline['data'] ?? null)) {
                continue;
            }

            return [
                'mime' => $inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png',
                'data' => preg_replace('/\s+/', '', (string) $inline['data']) ?? '',
            ];
        }

        $interactionImage = Arr::get($payload, 'output_image.data')
            ?? Arr::get($payload, 'outputs.0.data');

        if (filled($interactionImage)) {
            return [
                'mime' => (string) (Arr::get($payload, 'output_image.mime_type') ?? Arr::get($payload, 'outputs.0.mime_type') ?? 'image/png'),
                'data' => (string) $interactionImage,
            ];
        }

        return null;
    }
}
