<?php

namespace App\Livewire;

use App\Models\GalleryPhoto;
use App\Models\SocialPost;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Previous cakes')]
class GalleryPage extends Component
{
    #[Url]
    public string $tab = 'gallery';

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['gallery', 'social'], true) ? $tab : 'gallery';
    }

    public function render(): View
    {
        return view('livewire.gallery-page', [
            'photos' => GalleryPhoto::query()->active()->orderBy('sort')->orderByDesc('id')->get(),
            'posts' => SocialPost::query()->active()->orderBy('sort')->orderByDesc('posted_at')->get(),
        ]);
    }
}
