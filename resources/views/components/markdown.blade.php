@props([
    'content' => '',
])

<div {{ $attributes->class('markdown-answer') }}>
    {!! Illuminate\Support\Str::markdown((string) $content, [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ]) !!}
</div>
