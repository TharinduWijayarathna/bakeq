@php
    $links = [
        ['name' => 'Facebook', 'icon' => 'facebook', 'url' => config('services.social.facebook')],
        ['name' => 'Instagram', 'icon' => 'instagram', 'url' => config('services.social.instagram')],
        ['name' => 'TikTok', 'icon' => 'tiktok', 'url' => config('services.social.tiktok')],
        ['name' => 'WhatsApp', 'icon' => 'whatsapp', 'url' => config('services.social.whatsapp')],
    ];
@endphp

<nav {{ $attributes->merge(['class' => 'flex items-center gap-3']) }} aria-label="Social media">
    @foreach ($links as $link)
        <a
            href="{{ $link['url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            class="grid size-11 place-items-center rounded-full bg-card text-[color:var(--hero-deep)] shadow-soft transition hover:-translate-y-0.5"
            aria-label="{{ $link['name'] }}"
        >
            <x-icon :name="$link['icon']" class="size-5" />
        </a>
    @endforeach
</nav>
