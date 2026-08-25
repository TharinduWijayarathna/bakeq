<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        ?array $old = null,
        ?array $new = null,
        ?User $user = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => ($user ?? auth()->user())?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
