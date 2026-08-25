<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Audit log')]
class AuditLogIndex extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $action = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('audit'), 403);
    }

    public function render(): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when(filled($this->search), function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('action', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when(filled($this->action), fn ($query) => $query->where('action', $this->action))
            ->latest('id')
            ->limit(100)
            ->get();

        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('livewire.admin.audit-log-index', [
            'logs' => $logs,
            'actions' => $actions,
        ]);
    }
}
