<?php

namespace App\Livewire\Admin;

use App\Models\Cake;
use App\Models\Category;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class CakeForm extends Component
{
    use WithFileUploads;

    public ?Cake $cake = null;

    public string $name = '';

    public ?int $category_id = null;

    public string $description = '';

    public string $note = '';

    public string $price_rupees = '4500';

    public string $serves = '';

    public bool $is_active = true;

    public bool $is_featured = false;

    public mixed $image = null;

    public function mount(?Cake $cake = null): void
    {
        if ($cake?->exists) {
            $this->cake = $cake;
            $this->name = $cake->name;
            $this->category_id = $cake->category_id;
            $this->description = $cake->description ?? '';
            $this->note = $cake->note ?? '';
            $this->price_rupees = (string) Money::centsToRupees($cake->price);
            $this->serves = $cake->serves ?? '';
            $this->is_active = $cake->is_active;
            $this->is_featured = $cake->is_featured;
        }
    }

    public function save(): void
    {
        $this->authorize($this->cake?->exists ? 'update' : 'create', $this->cake ?? Cake::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
            'price_rupees' => ['required', 'numeric', 'min:0'],
            'serves' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $payload = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).($this->cake?->exists ? '' : '-'.Str::random(4)),
            'description' => $validated['description'] ?? null,
            'note' => $validated['note'] ?? null,
            'price' => Money::rupeesToCents($validated['price_rupees']),
            'serves' => $validated['serves'] ?? null,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
        ];

        if ($this->image instanceof TemporaryUploadedFile) {
            $payload['image_path'] = $this->image->store('cakes', 'public');
        }

        if ($this->cake?->exists) {
            unset($payload['slug']);
            $this->cake->update($payload);
        } else {
            $this->cake = Cake::query()->create($payload);
        }

        session()->flash('status', 'Cake saved.');
        $this->redirect(route('admin.cakes.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.cake-form', [
            'categories' => Category::query()->orderBy('sort')->get(),
        ])->title($this->cake?->exists ? 'Edit cake' : 'Add cake');
    }
}
