<?php

namespace App\Models;

use Database\Factories\SocialPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $platform
 * @property string $title
 * @property string $url
 * @property string|null $embed_html
 * @property string|null $image_path
 * @property Carbon|null $posted_at
 * @property bool $is_active
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['platform', 'title', 'url', 'embed_html', 'image_path', 'posted_at', 'is_active', 'sort'])]
class SocialPost extends Model
{
    /** @use HasFactory<SocialPostFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'sort' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @param  Builder<SocialPost>  $query
     * @return Builder<SocialPost>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function imageUrl(): ?string
    {
        if (blank($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, '/') || str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function platformLabel(): string
    {
        return match ($this->platform) {
            'tiktok' => 'TikTok',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            default => ucfirst($this->platform),
        };
    }
}
