<?php

namespace App\Livewire;

use App\Models\Cake;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class HomePage extends Component
{
    public function render(): View
    {
        return view('livewire.home-page', [
            'featuredCakes' => Cake::query()
                ->active()
                ->featured()
                ->with('category')
                ->latest()
                ->take(3)
                ->get(),
            'testimonials' => Testimonial::query()
                ->active()
                ->ordered()
                ->get(),
        ])->title('Home');
    }
}
