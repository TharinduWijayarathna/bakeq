<?php

namespace App\Support;

use Illuminate\Support\Str;

class Markdown
{
    /**
     * Normalize model output then render safe Markdown to HTML.
     */
    public static function render(string $content): string
    {
        return Str::markdown(self::normalize($content), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Clean common AI Markdown quirks so lists, tables, and emphasis render reliably.
     */
    public static function normalize(string $content): string
    {
        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));

        if ($content === '') {
            return '';
        }

        // Unwrap a single fenced block when the whole reply is wrapped as ```markdown ... ```
        if (preg_match('/^```(?:markdown|md|gfm)?\s*\n([\s\S]*?)\n```$/u', $content, $matches) === 1) {
            $content = trim($matches[1]);
        }

        // Insert a blank line before a list/heading only when the previous line is normal prose.
        $content = preg_replace(
            '/^(?![-*+] |\d+\. |#{1,6} |\|)(.+)\n(?=[-*+] |\d+\. |#{1,6} )/mu',
            "$1\n\n",
            $content,
        ) ?? $content;

        // Turn loose "Key: value" lines into bullets when they appear as a block after a blank line.
        $content = preg_replace_callback(
            '/(?:^|\n\n)((?:[A-Za-z][\w ]{0,40}:\s+.+\n?){2,})/u',
            function (array $matches): string {
                $block = trim($matches[1]);
                $lines = preg_split('/\n/', $block) ?: [];

                // Skip if this already looks like a Markdown list or table.
                if (collect($lines)->contains(fn (string $line): bool => preg_match('/^([-*+] |\d+\. |\|)/', $line) === 1)) {
                    return $matches[0];
                }

                $bullets = collect($lines)
                    ->map(function (string $line): string {
                        if (preg_match('/^([A-Za-z][\w ]{0,40}):\s*(.+)$/u', trim($line), $parts) !== 1) {
                            return '- '.trim($line);
                        }

                        return '- **'.trim($parts[1]).':** '.trim($parts[2]);
                    })
                    ->implode("\n");

                return "\n\n".$bullets."\n";
            },
            $content,
        ) ?? $content;

        return trim($content);
    }

    /**
     * Build a Markdown table from a list of associative rows.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columns
     */
    public static function table(array $rows, array $columns = []): string
    {
        if ($rows === []) {
            return '_No rows._';
        }

        if ($columns === []) {
            $columns = array_values(array_unique(array_merge(
                ...array_map(fn (array $row): array => array_keys($row), $rows),
            )));
        }

        $columns = array_values(array_filter(
            $columns,
            fn (string $column): bool => ! in_array($column, ['admin_url'], true),
        ));

        if ($columns === []) {
            return '_No rows._';
        }

        $escape = fn (mixed $value): string => str_replace('|', '\\|', trim((string) ($value ?? '—')));

        $header = '| '.implode(' | ', array_map($escape, $columns)).' |';
        $divider = '| '.implode(' | ', array_map(fn (): string => '---', $columns)).' |';
        $body = collect($rows)
            ->map(function (array $row) use ($columns, $escape): string {
                $cells = array_map(
                    fn (string $column): string => $escape($row[$column] ?? null),
                    $columns,
                );

                return '| '.implode(' | ', $cells).' |';
            })
            ->implode("\n");

        return $header."\n".$divider."\n".$body;
    }
}
