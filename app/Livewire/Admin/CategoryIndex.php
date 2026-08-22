<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Categories')]
class CategoryIndex extends Component
{
    public string $name = '';

    public int $sort = 0;

    public ?int $editingId = null;

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort' => ['required', 'integer', 'min:0'],
        ]);

        if ($this->editingId !== null) {
            $category = Category::query()->findOrFail($this->editingId);
            $category->update([
                'name' => $validated['name'],
                'sort' => $validated['sort'],
            ]);
        } else {
            Category::query()->create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'sort' => $validated['sort'],
                'is_active' => true,
            ]);
        }

        $this->reset('name', 'sort', 'editingId');
        session()->flash('status', 'Category saved.');
    }

    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->sort = $category->sort;
    }

    public function toggle(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function delete(int $categoryId): void
    {
        Category::query()->findOrFail($categoryId)->delete();
        session()->flash('status', 'Category removed.');
    }

    public function render(): View
    {
        return view('livewire.admin.category-index', [
            'categories' => Category::query()->withCount('cakes')->orderBy('sort')->get(),
        ]);
    }
}
