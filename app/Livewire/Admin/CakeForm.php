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

    public string $care_instructions = '';

    public string $note = '';

    public string $price_rupees = '4500';

    public string $base_price_rupees = '4500';

    public string $per_tier_addon_rupees = '0';

    public string $per_flavor_addon_rupees = '0';

    public string $serves = '';

    public string $lead_days = '3';

    public string $ingredients_text = '';

    public string $allergens_text = '';

    public string $size_options_text = '';

    public string $optional_addons_text = '';

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
            $this->care_instructions = $cake->care_instructions ?? '';
            $this->note = $cake->note ?? '';
            $this->price_rupees = (string) Money::centsToRupees($cake->price);
            $this->base_price_rupees = (string) Money::centsToRupees($cake->catalogBasePrice());
            $this->per_tier_addon_rupees = (string) Money::centsToRupees($cake->per_tier_addon);
            $this->per_flavor_addon_rupees = (string) Money::centsToRupees($cake->per_flavor_addon);
            $this->serves = $cake->serves ?? '';
            $this->lead_days = (string) $cake->lead_days;
            $this->ingredients_text = implode("\n", $cake->ingredients ?? []);
            $this->allergens_text = implode(', ', $cake->allergens ?? []);
            $this->size_options_text = $this->encodeSizeOptions($cake->size_options ?? []);
            $this->optional_addons_text = $this->encodeOptionalAddons($cake->optional_addons ?? []);
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
            'care_instructions' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
            'price_rupees' => ['required', 'numeric', 'min:0'],
            'base_price_rupees' => ['required', 'numeric', 'min:0'],
            'per_tier_addon_rupees' => ['required', 'numeric', 'min:0'],
            'per_flavor_addon_rupees' => ['required', 'numeric', 'min:0'],
            'serves' => ['nullable', 'string', 'max:50'],
            'lead_days' => ['required', 'integer', 'min:0', 'max:60'],
            'ingredients_text' => ['nullable', 'string'],
            'allergens_text' => ['nullable', 'string', 'max:1000'],
            'size_options_text' => ['nullable', 'string'],
            'optional_addons_text' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $payload = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).($this->cake?->exists ? '' : '-'.Str::random(4)),
            'description' => $validated['description'] ?? null,
            'care_instructions' => $validated['care_instructions'] ?? null,
            'note' => $validated['note'] ?? null,
            'price' => Money::rupeesToCents($validated['price_rupees']),
            'base_price' => Money::rupeesToCents($validated['base_price_rupees']),
            'per_tier_addon' => Money::rupeesToCents($validated['per_tier_addon_rupees']),
            'per_flavor_addon' => Money::rupeesToCents($validated['per_flavor_addon_rupees']),
            'optional_addons' => $this->parseOptionalAddons($validated['optional_addons_text'] ?? ''),
            'serves' => $validated['serves'] ?? null,
            'size_options' => $this->parseSizeOptions($validated['size_options_text'] ?? ''),
            'ingredients' => $this->parseLines($validated['ingredients_text'] ?? ''),
            'allergens' => $this->parseCsv($validated['allergens_text'] ?? ''),
            'lead_days' => (int) $validated['lead_days'],
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

    /**
     * @return list<string>
     */
    private function parseLines(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function parseCsv(string $text): array
    {
        return collect(explode(',', $text))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $sizes
     */
    private function encodeSizeOptions(array $sizes): string
    {
        return collect($sizes)
            ->map(function (mixed $size): string {
                if (! is_array($size)) {
                    return '';
                }

                $label = (string) ($size['label'] ?? '');
                $servings = (string) ($size['servings'] ?? '');
                $price = Money::centsToRupees((int) ($size['price'] ?? 0));

                return trim($label.'|'.$servings.'|'.$price, '|');
            })
            ->filter()
            ->implode("\n");
    }

    /**
     * @return list<array{label: string, servings: string, price: int}>
     */
    private function parseSizeOptions(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(function (string $line): ?array {
                $parts = array_map('trim', explode('|', $line));

                if (($parts[0] ?? '') === '') {
                    return null;
                }

                return [
                    'label' => $parts[0],
                    'servings' => $parts[1] ?? '',
                    'price' => Money::rupeesToCents($parts[2] ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $addons
     */
    private function encodeOptionalAddons(array $addons): string
    {
        return collect($addons)
            ->map(function (mixed $addon): string {
                if (! is_array($addon)) {
                    return '';
                }

                $name = (string) ($addon['name'] ?? '');
                $price = Money::centsToRupees((int) ($addon['price'] ?? 0));

                return $name === '' ? '' : $name.'|'.$price;
            })
            ->filter()
            ->implode("\n");
    }

    /**
     * @return list<array{name: string, price: int}>
     */
    private function parseOptionalAddons(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(function (string $line): ?array {
                $parts = array_map('trim', explode('|', $line));

                if (($parts[0] ?? '') === '') {
                    return null;
                }

                return [
                    'name' => $parts[0],
                    'price' => Money::rupeesToCents($parts[1] ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
