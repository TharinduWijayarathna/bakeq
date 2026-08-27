@props([
    'content' => '',
])

<div {{ $attributes->class('markdown-answer') }}>
    {!! \App\Support\Markdown::render((string) $content) !!}
</div>
