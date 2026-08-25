<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\CakeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $care_instructions
 * @property string|null $note
 * @property int $price
 * @property int|null $base_price
 * @property int $per_tier_addon
 * @property int $per_flavor_addon
 * @property array<int, array{name: string, price: int}>|null $optional_addons
 * @property string|null $serves
 * @property array<int, array{label: string, servings: string, price: int}>|null $size_options
 * @property list<string>|null $ingredients
 * @property list<string>|null $allergens
 * @property int $lead_days
 * @property string|null $image_path
 * @property bool $is_active
 * @property bool $is_featured
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'care_instructions',
    'note',
    'price',
    'base_price',
    'per_tier_addon',
    'per_flavor_addon',
    'optional_addons',
    'serves',
    'size_options',
    'ingredients',
    'allergens',
    'lead_days',
    'image_path',
    'is_active',
    'is_featured',
])]
class Cake extends Model
{
    /** @use HasFactory<CakeFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'lead_days' => 3,
        'per_tier_addon' => 0,
        'per_flavor_addon' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Cake $cake): void {
            if (blank($cake->slug)) {
                $cake->slug = Str::slug($cake->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'base_price' => 'integer',
            'per_tier_addon' => 'integer',
            'per_flavor_addon' => 'integer',
            'optional_addons' => 'array',
            'size_options' => 'array',
            'ingredients' => 'array',
            'allergens' => 'array',
            'lead_days' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Cake>  $query
     * @return Builder<Cake>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Cake>  $query
     * @return Builder<Cake>
     */
    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function catalogBasePrice(): int
    {
        return $this->base_price ?? $this->price;
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price);
    }

    public function formattedBasePrice(): string
    {
        return Money::format($this->catalogBasePrice());
    }

    public function formattedPerTierAddon(): string
    {
        return Money::format($this->per_tier_addon);
    }

    public function formattedPerFlavorAddon(): string
    {
        return Money::format($this->per_flavor_addon);
    }

    /**
     * @return Collection<int, array{name: string, price: int, formatted_price: string}>
     */
    public function optionalAddonRows(): Collection
    {
        return Collection::make($this->optional_addons ?? [])
            ->map(function (mixed $addon): array {
                $name = is_array($addon) ? (string) ($addon['name'] ?? '') : '';
                $price = is_array($addon) ? (int) ($addon['price'] ?? 0) : 0;

                return [
                    'name' => $name,
                    'price' => $price,
                    'formatted_price' => Money::format($price),
                ];
            })
            ->filter(fn (array $addon): bool => $addon['name'] !== '')
            ->values();
    }

    /**
     * @return Collection<int, array{label: string, servings: string, price: int, formatted_price: string}>
     */
    public function sizeOptionRows(): Collection
    {
        return Collection::make($this->size_options ?? [])
            ->map(function (mixed $size): array {
                $label = is_array($size) ? (string) ($size['label'] ?? '') : '';
                $servings = is_array($size) ? (string) ($size['servings'] ?? '') : '';
                $price = is_array($size) ? (int) ($size['price'] ?? 0) : 0;

                return [
                    'label' => $label,
                    'servings' => $servings,
                    'price' => $price,
                    'formatted_price' => Money::format($price),
                ];
            })
            ->filter(fn (array $size): bool => $size['label'] !== '')
            ->values();
    }

    public function imageUrl(): string
    {
        if (blank($this->image_path)) {
            return asset('images/cakes/birthday.jpg');
        }

        if (str_starts_with($this->image_path, '/') || str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
