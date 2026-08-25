<?php

namespace App\Models;

use Database\Factories\GalleryPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $title
 * @property string $image_path
 * @property int $sort
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'image_path', 'sort', 'is_active'])]
class GalleryPhoto extends Model
{
    /** @use HasFactory<GalleryPhotoFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<GalleryPhoto>  $query
     * @return Builder<GalleryPhoto>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function imageUrl(): string
    {
        if (str_starts_with($this->image_path, '/') || str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
