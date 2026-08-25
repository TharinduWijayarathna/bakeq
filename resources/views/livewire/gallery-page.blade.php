<div class="mx-auto max-w-6xl px-5 py-12">
    <p class="font-script text-3xl text-primary">Portfolio</p>
    <h1 class="mt-1 text-4xl sm:text-5xl">Previous cakes</h1>
    <p class="mt-3 max-w-xl text-sm text-muted-foreground">A look at cakes we have already baked, plus recent social highlights.</p>

    <div class="mt-6 flex flex-wrap gap-2">
        <button type="button" wire:click="setTab('gallery')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'gallery' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Gallery</button>
        <button type="button" wire:click="setTab('social')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'social' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Social</button>
    </div>

    @if ($tab === 'gallery')
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($photos as $photo)
                <figure wire:key="photo-{{ $photo->id }}" class="overflow-hidden rounded-4xl bg-card shadow-soft">
                    <img src="{{ $photo->imageUrl() }}" alt="{{ $photo->title }}" class="aspect-square w-full object-cover" loading="lazy">
                    <figcaption class="px-4 py-3 text-sm font-semibold">{{ $photo->title }}</figcaption>
                </figure>
            @empty
                <p class="text-sm text-muted-foreground sm:col-span-2 lg:col-span-3">Gallery photos will appear here once the bakery uploads them.</p>
            @endforelse
        </div>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            @forelse ($posts as $post)
                <article wire:key="post-{{ $post->id }}" class="rounded-4xl bg-card p-5 shadow-soft">
                    <p class="text-xs font-bold uppercase tracking-wider text-primary">{{ $post->platformLabel() }}</p>
                    <h2 class="mt-2 text-xl font-bold">{{ $post->title }}</h2>
                    @if (filled($post->embed_html))
                        <div class="mt-4 overflow-hidden rounded-2xl bg-muted/40 p-2 text-sm">
                            {!! $post->embed_html !!}
                        </div>
                    @elseif ($post->imageUrl())
                        <img src="{{ $post->imageUrl() }}" alt="" class="mt-4 aspect-video w-full rounded-2xl object-cover">
                    @endif
                    <a href="{{ $post->url }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex text-sm font-semibold text-primary">View on {{ $post->platformLabel() }}</a>
                </article>
            @empty
                <p class="text-sm text-muted-foreground sm:col-span-2">Social posts will appear here when connected accounts are curated in admin.</p>
            @endforelse
        </div>
        <p class="mt-6 text-xs text-muted-foreground">Posts are curated manually (URL + optional official embed HTML). We do not scrape social platforms.</p>
    @endif
</div>
