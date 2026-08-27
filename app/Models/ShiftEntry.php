<?php

namespace App\Models;

use Database\Factories\ShiftEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property Carbon $clocked_in_at
 * @property Carbon|null $clocked_out_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Shift|null $shift
 */
#[Fillable(['user_id', 'shift_id', 'clocked_in_at', 'clocked_out_at', 'notes'])]
class ShiftEntry extends Model
{
    /** @use HasFactory<ShiftEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clocked_in_at' => 'datetime',
            'clocked_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function isOpen(): bool
    {
        return $this->clocked_out_at === null;
    }

    public function durationMinutes(): int
    {
        $end = $this->clocked_out_at ?? now();

        return max(0, (int) $this->clocked_in_at->diffInMinutes($end));
    }

    public function durationLabel(): string
    {
        $minutes = $this->durationMinutes();
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        if ($hours === 0) {
            return $remainder.'m';
        }

        if ($remainder === 0) {
            return $hours.'h';
        }

        return $hours.'h '.$remainder.'m';
    }
}
