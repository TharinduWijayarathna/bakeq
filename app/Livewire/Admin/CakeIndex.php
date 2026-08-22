<?php

namespace App\Livewire\Admin;

use App\Models\Cake;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Cakes')]
class CakeIndex extends Component
{
    public function delete(int $cakeId): void
    {
        Cake::query()->findOrFail($cakeId)->delete();
        session()->flash('status', 'Cake removed.');
    }

    public function render(): View
    {
        return view('livewire.admin.cake-index', [
            'cakes' => Cake::query()->with('category')->latest()->get(),
        ]);
    }
}
