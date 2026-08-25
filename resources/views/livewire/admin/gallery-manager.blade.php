<div>
    <p class="font-script text-3xl text-primary">Showcase</p>
    <h1 class="mt-1 text-4xl">Gallery & social</h1>
    <x-flash />

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl font-bold">Previous cake photo</h2>
            <form wire:submit="savePhoto" class="mt-4 space-y-3">
                <input type="text" wire:model="photo_title" placeholder="Title" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('photo_title') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                <input type="file" wire:model="photo_upload" accept="image/*" class="block w-full text-sm">
                @error('photo_upload') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">Upload photo</button>
            </form>
            <ul class="mt-6 space-y-3">
                @foreach ($photos as $photo)
                    <li wire:key="gphoto-{{ $photo->id }}" class="flex items-center gap-3">
                        <img src="{{ $photo->imageUrl() }}" alt="" class="size-14 rounded-xl object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $photo->title }}</p>
                            <p class="text-xs text-muted-foreground">{{ $photo->is_active ? 'Visible' : 'Hidden' }}</p>
                        </div>
                        <button type="button" wire:click="togglePhoto({{ $photo->id }})" class="text-xs font-bold uppercase">Toggle</button>
                        <button type="button" wire:click="deletePhoto({{ $photo->id }})" class="text-xs font-bold uppercase text-destructive">Delete</button>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl font-bold">Social post (manual grid / embed)</h2>
            <p class="mt-1 text-xs text-muted-foreground">Paste a public post URL. Optional embed HTML from the platform’s oEmbed/share tools — no scraping.</p>
            <form wire:submit="savePost" class="mt-4 space-y-3">
                <select wire:model="post_platform" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                    <option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option>
                    <option value="facebook">Facebook</option>
                </select>
                <input type="text" wire:model="post_title" placeholder="Title" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                <input type="url" wire:model="post_url" placeholder="https://…" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                <textarea wire:model="post_embed_html" rows="3" placeholder="Optional embed HTML" class="w-full rounded-2xl border border-input px-4 py-3 text-sm"></textarea>
                @error('post_url') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">Add post</button>
            </form>
            <ul class="mt-6 space-y-3">
                @foreach ($posts as $post)
                    <li wire:key="spost-{{ $post->id }}" class="rounded-2xl bg-muted/40 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $post->title }}</p>
                                <p class="text-xs text-muted-foreground">{{ $post->platformLabel() }} · {{ $post->is_active ? 'Visible' : 'Hidden' }}</p>
                                <a href="{{ $post->url }}" target="_blank" class="text-xs font-semibold text-primary">Open link</a>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="togglePost({{ $post->id }})" class="text-xs font-bold uppercase">Toggle</button>
                                <button type="button" wire:click="deletePost({{ $post->id }})" class="text-xs font-bold uppercase text-destructive">Delete</button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
