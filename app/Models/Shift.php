<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property ShiftStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'starts_at',
    'ends_at',
    'status',
    'notes',
])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'scheduled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => ShiftStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ShiftEntry::class);
    }

    public function openEntry(): ?ShiftEntry
    {
        return $this->entries()->whereNull('clocked_out_at')->latest('clocked_in_at')->first();
    }

    public function isOpen(): bool
    {
        return $this->status === ShiftStatus::InProgress;
    }

    public function canClockIn(): bool
    {
        return in_array($this->status, [ShiftStatus::Scheduled, ShiftStatus::Missed], true);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [ShiftStatus::Scheduled, ShiftStatus::Missed], true);
    }

    public function plannedMinutes(): int
    {
        return max(0, (int) $this->starts_at->diffInMinutes($this->ends_at));
    }

    public function workedMinutes(): int
    {
        return (int) $this->entries
            ->filter(fn (ShiftEntry $entry): bool => $entry->clocked_out_at !== null)
            ->sum(fn (ShiftEntry $entry): int => $entry->durationMinutes());
    }

    public function windowLabel(): string
    {
        return $this->starts_at->format('g:i A').' – '.$this->ends_at->format('g:i A');
    }

    public function markMissedIfPast(): void
    {
        if ($this->status !== ShiftStatus::Scheduled) {
            return;
        }

        if ($this->ends_at->isFuture()) {
            return;
        }

        if ($this->entries()->exists()) {
            return;
        }

        $this->update(['status' => ShiftStatus::Missed]);
    }
}
