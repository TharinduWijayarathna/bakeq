<?php

namespace App\Livewire\Admin;

use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Testimonials')]
class TestimonialIndex extends Component
{
    public string $quote = '';

    public string $author = '';

    public string $occasion = '';

    public int $rating = 5;

    public int $sort = 0;

    public ?int $editingId = null;

    public function save(): void
    {
        $validated = $this->validate([
            'quote' => ['required', 'string', 'max:500'],
            'author' => ['required', 'string', 'max:255'],
            'occasion' => ['nullable', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'sort' => ['required', 'integer', 'min:0'],
        ]);

        $payload = [
            'quote' => $validated['quote'],
            'author' => $validated['author'],
            'occasion' => $validated['occasion'] ?: null,
            'rating' => $validated['rating'],
            'sort' => $validated['sort'],
        ];

        if ($this->editingId !== null) {
            Testimonial::query()->findOrFail($this->editingId)->update($payload);
        } else {
            Testimonial::query()->create([...$payload, 'is_active' => true]);
        }

        $this->resetForm();
        session()->flash('status', 'Testimonial saved.');
    }

    public function edit(int $testimonialId): void
    {
        $testimonial = Testimonial::query()->findOrFail($testimonialId);
        $this->editingId = $testimonial->id;
        $this->quote = $testimonial->quote;
        $this->author = $testimonial->author;
        $this->occasion = $testimonial->occasion ?? '';
        $this->rating = $testimonial->rating;
        $this->sort = $testimonial->sort;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function toggle(int $testimonialId): void
    {
        $testimonial = Testimonial::query()->findOrFail($testimonialId);
        $testimonial->update(['is_active' => ! $testimonial->is_active]);
    }

    public function delete(int $testimonialId): void
    {
        Testimonial::query()->findOrFail($testimonialId)->delete();
        session()->flash('status', 'Testimonial removed.');
    }

    public function render(): View
    {
        return view('livewire.admin.testimonial-index', [
            'testimonials' => Testimonial::query()->ordered()->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset('quote', 'author', 'occasion', 'rating', 'sort', 'editingId');
    }
}
