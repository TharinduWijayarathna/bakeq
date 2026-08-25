<?php

namespace App\Livewire\Admin;

use App\Models\GalleryPhoto;
use App\Models\SocialPost;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Gallery')]
class GalleryManager extends Component
{
    use WithFileUploads;

    public string $photo_title = '';

    public $photo_upload = null;

    public string $post_platform = 'instagram';

    public string $post_title = '';

    public string $post_url = '';

    public string $post_embed_html = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('gallery'), 403);
    }

    public function savePhoto(): void
    {
        abort_unless(auth()->user()?->canAccess('gallery'), 403);

        $validated = $this->validate([
            'photo_title' => ['required', 'string', 'max:255'],
            'photo_upload' => ['required', 'image', 'max:5120'],
        ]);

        $path = $this->photo_upload->store('gallery', 'public');

        $photo = GalleryPhoto::query()->create([
            'title' => $validated['photo_title'],
            'image_path' => $path,
            'sort' => (int) GalleryPhoto::query()->max('sort') + 1,
            'is_active' => true,
        ]);

        AuditLogger::record('gallery.photo_created', $photo, null, [
            'title' => $photo->title,
        ]);

        $this->reset(['photo_title', 'photo_upload']);
        session()->flash('status', 'Gallery photo added.');
    }

    public function togglePhoto(int $id): void
    {
        abort_unless(auth()->user()?->canAccess('gallery'), 403);

        $photo = GalleryPhoto::query()->findOrFail($id);
        $photo->update(['is_active' => ! $photo->is_active]);

        AuditLogger::record('gallery.photo_toggled', $photo, null, [
            'is_active' => $photo->is_active,
        ]);
    }

    public function deletePhoto(int $id): void
    {
        abort_unless(auth()->user()?->canAccess('gallery'), 403);

        $photo = GalleryPhoto::query()->findOrFail($id);

        if (! str_starts_with($photo->image_path, '/') && ! str_starts_with($photo->image_path, 'http')) {
            Storage::disk('public')->delete($photo->image_path);
        }

        AuditLogger::record('gallery.photo_deleted', null, ['title' => $photo->title], null);
        $photo->delete();
        session()->flash('status', 'Photo removed.');
    }

    public function savePost(): void
    {
        abort_unless(auth()->user()?->canAccess('gallery'), 403);

        $validated = $this->validate([
            'post_platform' => ['required', 'in:tiktok,instagram,facebook'],
            'post_title' => ['required', 'string', 'max:255'],
            'post_url' => ['required', 'url', 'max:500'],
            'post_embed_html' => ['nullable', 'string', 'max:5000'],
        ]);

        $post = SocialPost::query()->create([
            'platform' => $validated['post_platform'],
            'title' => $validated['post_title'],
            'url' => $validated['post_url'],
            'embed_html' => filled($validated['post_embed_html']) ? $validated['post_embed_html'] : null,
            'posted_at' => now(),
            'is_active' => true,
            'sort' => (int) SocialPost::query()->max('sort') + 1,
        ]);

        AuditLogger::record('gallery.social_post_created', $post, null, [
            'platform' => $post->platform,
            'title' => $post->title,
        ]);

        $this->reset(['post_title', 'post_url', 'post_embed_html']);
        $this->post_platform = 'instagram';
        session()->flash('status', 'Social post added.');
    }

    public function togglePost(int $id): void
    {
        abort_unless(auth()->user()?->canAccess('gallery'), 403);

        $post = SocialPost::query()->findOrFail($id);
        $post->update(['is_active' => ! $post->is_active]);
    }

    public function deletePost(int $id): void
    {
        abort_unless(auth()->user()?->canAccess('gallery'), 403);

        $post = SocialPost::query()->findOrFail($id);
        AuditLogger::record('gallery.social_post_deleted', null, ['title' => $post->title], null);
        $post->delete();
        session()->flash('status', 'Social post removed.');
    }

    public function render(): View
    {
        return view('livewire.admin.gallery-manager', [
            'photos' => GalleryPhoto::query()->orderBy('sort')->orderByDesc('id')->get(),
            'posts' => SocialPost::query()->orderBy('sort')->orderByDesc('id')->get(),
        ]);
    }
}
