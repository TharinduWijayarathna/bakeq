<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\CakeDesignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property array<string, mixed> $selections
 * @property int $tiers
 * @property string|null $preview_path
 * @property int $estimated_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'selections', 'tiers', 'preview_path', 'estimated_price'])]
class CakeDesign extends Model
{
    /** @use HasFactory<CakeDesignFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selections' => 'array',
            'tiers' => 'integer',
            'estimated_price' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedPrice(): string
    {
        return Money::format($this->estimated_price);
    }

    public function previewUrl(): string
    {
        if (blank($this->preview_path)) {
            return '/images/previews/preview-1.jpg';
        }

        if (str_starts_with($this->preview_path, 'http://') || str_starts_with($this->preview_path, 'https://')) {
            return $this->preview_path;
        }

        if (str_starts_with($this->preview_path, '/')) {
            return $this->preview_path;
        }

        if (str_starts_with($this->preview_path, 'images/')) {
            return '/'.$this->preview_path;
        }

        return '/storage/'.$this->preview_path;
    }
}
