<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\DesignerOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $designer_option_group_id
 * @property string $name
 * @property string|null $description
 * @property string|null $color_hex
 * @property int $extra_price
 * @property string|null $image_path
 * @property int $sort
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['designer_option_group_id', 'name', 'description', 'color_hex', 'extra_price', 'image_path', 'sort', 'is_active'])]
class DesignerOption extends Model
{
    /** @use HasFactory<DesignerOptionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'extra_price' => 0,
        'sort' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extra_price' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<DesignerOption>  $query
     * @return Builder<DesignerOption>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DesignerOptionGroup::class, 'designer_option_group_id');
    }

    public function formattedExtraPrice(): string
    {
        return Money::format($this->extra_price);
    }

    public function illustrationUrl(): ?string
    {
        if (filled($this->image_path)) {
            if (str_starts_with($this->image_path, '/') || str_starts_with($this->image_path, 'http')) {
                return $this->image_path;
            }

            return asset($this->image_path);
        }

        $fallback = 'images/designer/types/'.Str::slug($this->name).'.svg';

        if (is_file(public_path($fallback))) {
            return asset($fallback);
        }

        return null;
    }
}
