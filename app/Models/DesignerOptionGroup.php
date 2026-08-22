<?php

namespace App\Models;

use App\Enums\SelectionType;
use Database\Factories\DesignerOptionGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property SelectionType $selection_type
 * @property bool $is_required
 * @property int $min_select
 * @property int $max_select
 * @property int $sort
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'selection_type', 'is_required', 'min_select', 'max_select', 'sort', 'is_active'])]
class DesignerOptionGroup extends Model
{
    /** @use HasFactory<DesignerOptionGroupFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'selection_type' => 'single',
        'is_required' => true,
        'min_select' => 1,
        'max_select' => 1,
        'sort' => 0,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (DesignerOptionGroup $group): void {
            if (blank($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selection_type' => SelectionType::class,
            'is_required' => 'boolean',
            'min_select' => 'integer',
            'max_select' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<DesignerOptionGroup>  $query
     * @return Builder<DesignerOptionGroup>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function options(): HasMany
    {
        return $this->hasMany(DesignerOption::class)->orderBy('sort');
    }
}
