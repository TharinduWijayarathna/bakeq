<?php

namespace App\Ai;

use App\Models\User;
use App\Support\AdminAgentTools;
use App\Support\Brand;
use App\Support\Markdown;
use Illuminate\Support\Arr;
use Throwable;

class AdminAgent
{
    private const MAX_TOOL_ROUNDS = 6;

    public function __construct(private GeminiClient $gemini) {}

    /**
     * @param  list<array{role: string, body: string}>  $history
     * @return array{answer: string, tools_used: list<string>}
     */
    public function reply(string $message, array $history, User $actor): array
    {
        if (! filled(config('services.gemini.key'))) {
            return $this->demoReply($message, $actor);
        }

        try {
            return $this->geminiReply($message, $history, $actor);
        } catch (Throwable $exception) {
            report($exception);

            return $this->demoReply($message, $actor);
        }
    }

    /**
     * @param  list<array{role: string, body: string}>  $history
     * @return array{answer: string, tools_used: list<string>}
     */
    private function geminiReply(string $message, array $history, User $actor): array
    {
        $contents = $this->contents($message, $history);
        $toolsUsed = [];

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $payload = $this->gemini->generateContent(
                (string) config('services.gemini.model'),
                [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $this->systemPrompt($actor)],
                        ],
                    ],
                    'contents' => $contents,
                    'tools' => [
                        [
                            'functionDeclarations' => AdminAgentTools::declarations(),
                        ],
                    ],
                    'toolConfig' => [
                        'functionCallingConfig' => [
                            'mode' => 'AUTO',
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 900,
                    ],
                ],
                timeout: 45,
            );

            $modelContent = Arr::get($payload, 'candidates.0.content');
            $parts = Arr::get($modelContent, 'parts', []);

            if (! is_array($parts) || $parts === []) {
                break;
            }

            $functionCalls = [];

            foreach ($parts as $part) {
                if (! is_array($part)) {
                    continue;
                }

                $call = $part['functionCall'] ?? $part['function_call'] ?? null;

                if (is_array($call) && filled($call['name'] ?? null)) {
                    $functionCalls[] = $call;
                }
            }

            if ($functionCalls === []) {
                $text = $this->gemini->extractText($payload);

                return [
                    'answer' => $text !== '' ? $text : 'I could not generate a reply. Try rephrasing the request.',
                    'tools_used' => array_values(array_unique($toolsUsed)),
                ];
            }

            $contents[] = [
                'role' => 'model',
                'parts' => $parts,
            ];

            $responseParts = [];

            foreach ($functionCalls as $call) {
                $name = (string) $call['name'];
                /** @var array<string, mixed> $args */
                $args = is_array($call['args'] ?? null) ? $call['args'] : [];
                $result = AdminAgentTools::call($name, $args, $actor);
                $toolsUsed[] = $name;
                $responseParts[] = [
                    'functionResponse' => [
                        'name' => $name,
                        'response' => [
                            'ok' => $result['ok'],
                            'summary' => $result['summary'],
                            'data' => $result['data'] ?? null,
                        ],
                    ],
                ];
            }

            $contents[] = [
                'role' => 'user',
                'parts' => $responseParts,
            ];
        }

        return [
            'answer' => 'I hit the tool-call limit while working on that. Ask me to continue with a shorter follow-up.',
            'tools_used' => array_values(array_unique($toolsUsed)),
        ];
    }

    /**
     * Offline / no-key fallback that still runs real tools for common admin asks.
     *
     * @return array{answer: string, tools_used: list<string>}
     */
    private function demoReply(string $message, User $actor): array
    {
        $normalized = mb_strtolower(trim($message));
        $toolsUsed = [];

        $dispatch = function (string $name, array $args = []) use ($actor, &$toolsUsed): array {
            $toolsUsed[] = $name;

            return AdminAgentTools::call($name, $args, $actor);
        };

        if (preg_match('/\border\s*#?\s*(\d+)\b/i', $message, $matches)
            || preg_match('/\b(?:status|details|show|get)\s+(?:of\s+)?(?:order\s*)?#?(\d+)\b/i', $message, $matches)) {
            $result = $dispatch('get_order', ['order_id' => (int) $matches[1]]);

            if (
                $result['ok']
                && preg_match('/\b(?:mark|set|update|change)\b.*\b(pending|confirmed|baking|delivered|cancelled)\b/i', $message, $statusMatch)
            ) {
                $result = $dispatch('update_order_status', [
                    'order_id' => (int) $matches[1],
                    'status' => strtolower($statusMatch[1]),
                ]);
            }

            return $this->formatDemo($result, $toolsUsed);
        }

        if (str_contains($normalized, 'low stock') || str_contains($normalized, 'reorder')) {
            return $this->formatDemo($dispatch('list_low_stock'), $toolsUsed);
        }

        if (str_contains($normalized, 'dashboard') || str_contains($normalized, 'summary') || str_contains($normalized, 'revenue')) {
            return $this->formatDemo($dispatch('get_dashboard_summary'), $toolsUsed);
        }

        if (str_contains($normalized, 'categor')) {
            if (preg_match('/\bcreate\s+categor(?:y|ies)\s+[\'"]?([^\'"\n]+)[\'"]?/i', $message, $matches)) {
                return $this->formatDemo($dispatch('create_category', ['name' => trim($matches[1])]), $toolsUsed);
            }

            return $this->formatDemo($dispatch('list_categories'), $toolsUsed);
        }

        if (str_contains($normalized, 'employee') || str_contains($normalized, 'staff')) {
            return $this->formatDemo($dispatch('list_employees'), $toolsUsed);
        }

        if (preg_match('/\b(?:find|search|look\s*up)\s+customer[s]?\s+(.+)$/i', $message, $matches)) {
            return $this->formatDemo($dispatch('search_customers', ['query' => trim($matches[1])]), $toolsUsed);
        }

        if (preg_match('/\b(?:find|search|look\s*up)\s+cake[s]?\s+(.+)$/i', $message, $matches)) {
            return $this->formatDemo($dispatch('search_cakes', ['query' => trim($matches[1])]), $toolsUsed);
        }

        if (preg_match('/\b(?:find|search|look\s*up)\s+order[s]?\s+(.+)$/i', $message, $matches)) {
            return $this->formatDemo($dispatch('search_orders', ['query' => trim($matches[1])]), $toolsUsed);
        }

        if (str_contains($normalized, 'pending order')) {
            return $this->formatDemo($dispatch('search_orders', ['status' => 'pending']), $toolsUsed);
        }

        return [
            'answer' => "**Admin Agent (tools-only mode)**\n\nFull chat AI is not configured, but I can still run bakery tools with clear requests, for example:\n\n- Status of order #12\n- Show low stock\n- Dashboard summary\n- List categories\n- Find customer Nimal\n- Search cakes chocolate\n- Pending orders\n\nConfigure the AI API key for natural-language agentic chat.",
            'tools_used' => [],
        ];
    }

    /**
     * @param  array{ok: bool, summary: string, data?: mixed}  $result
     * @param  list<string>  $toolsUsed
     * @return array{answer: string, tools_used: list<string>}
     */
    private function formatDemo(array $result, array $toolsUsed): array
    {
        $lines = [
            $result['ok'] ? '**Done**' : '**Could not complete**',
            '',
            $result['summary'],
        ];

        if ($result['ok'] && isset($result['data']) && is_array($result['data'])) {
            $lines[] = '';
            $lines[] = $this->markdownData($result['data']);
        }

        return [
            'answer' => implode("\n", $lines),
            'tools_used' => array_values(array_unique($toolsUsed)),
        ];
    }

    private function markdownData(mixed $data): string
    {
        if (! is_array($data)) {
            return '';
        }

        if ($data === []) {
            return '_No rows._';
        }

        if (array_is_list($data)) {
            $rows = collect($data)
                ->take(12)
                ->filter(fn (mixed $row): bool => is_array($row))
                ->values()
                ->all();

            if ($rows !== [] && collect($rows)->every(fn (array $row): bool => $row !== [] && ! array_is_list($row))) {
                $preferred = ['id', 'name', 'status_label', 'status', 'production_label', 'total_due', 'stock_label', 'email', 'role_label', 'price', 'customer'];
                $available = array_values(array_unique(array_merge(
                    ...array_map(fn (array $row): array => array_keys($row), $rows),
                )));
                $columns = array_values(array_filter(
                    $preferred,
                    fn (string $column): bool => in_array($column, $available, true),
                ));

                if (count($columns) < 2) {
                    $columns = array_slice($available, 0, 5);
                }

                return Markdown::table($rows, $columns);
            }

            return collect($data)
                ->take(12)
                ->map(function (mixed $row): string {
                    if (! is_array($row)) {
                        return '- '.(string) $row;
                    }

                    $label = $row['name']
                        ?? (isset($row['id']) ? '#'.$row['id'] : null)
                        ?? json_encode($row);

                    $bits = collect($row)
                        ->except(['admin_url', 'name', 'id'])
                        ->filter(fn (mixed $value): bool => is_scalar($value) || $value === null)
                        ->take(4)
                        ->map(fn (mixed $value, string|int $key): string => $key.': '.(string) $value)
                        ->implode(', ');

                    return '- **'.$label.'**'.($bits !== '' ? ' — '.$bits : '');
                })
                ->implode("\n");
        }

        return collect($data)
            ->filter(fn (mixed $value): bool => is_scalar($value) || $value === null)
            ->map(fn (mixed $value, string|int $key): string => '- **'.$key.':** '.(string) $value)
            ->implode("\n");
    }

    /**
     * @param  list<array{role: string, body: string}>  $history
     * @return list<array{role: string, parts: list<array{text: string}>}>
     */
    private function contents(string $message, array $history): array
    {
        $contents = [];

        foreach (array_slice($history, -12) as $entry) {
            $contents[] = [
                'role' => $entry['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [
                    ['text' => $entry['body']],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        return $contents;
    }

    private function systemPrompt(User $actor): string
    {
        return 'You are the '.Brand::name().' Admin Agent inside the bakery admin panel.
You help staff run the shop: orders, production, cakes, categories, customers, inventory, waste, POS, and dashboard insights.
The current user is '.$actor->name.' (role: '.$actor->role->value.'). Only call tools they are allowed to use; tools enforce permissions.
Use tools whenever data or mutations are needed. Prefer tools over guessing IDs, prices, or stock.
Always reply in clean GitHub-flavored Markdown (never wrap the whole answer in a ``` fence):
- Start with a short bold outcome line
- Use bullet lists for a few facts
- Use Markdown tables when returning multiple records (orders, cakes, stock, customers, staff)
- Use `code` for IDs, statuses, and tool names
- Include clickable links when tool data provides admin_url
Prices in the app are stored in cents but tools accept rupees for money inputs.
Never invent successful writes — only report what tools returned.
If a request is unsafe or out of scope (passwords, deleting the database, unrelated topics), refuse briefly and steer back to bakery operations.
Today is '.now()->toDateString().'.';
    }
}
