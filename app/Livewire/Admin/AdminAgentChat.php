<?php

namespace App\Livewire\Admin;

use App\Ai\AdminAgent;
use App\Models\AssistantMessage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Admin Agent')]
class AdminAgentChat extends Component
{
    public string $message = '';

    /** @var list<array{role: string, body: string, tools: list<string>}> */
    public array $messages = [];

    #[Locked]
    public string $sessionId = '';

    /** @var list<string> */
    public array $lastTools = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('admin-agent'), 403);

        $this->sessionId = 'admin-agent-'.auth()->id();
        $this->restoreHistory();
    }

    public function askSuggestion(string $question, AdminAgent $agent): void
    {
        $this->message = $question;
        $this->send($agent);
    }

    public function send(AdminAgent $agent): void
    {
        abort_unless(auth()->user()?->canAccess('admin-agent'), 403);

        $validated = $this->validate([
            'message' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $history = collect($this->messages)
            ->map(fn (array $entry): array => [
                'role' => $entry['role'],
                'body' => $entry['body'],
            ])
            ->all();

        AssistantMessage::query()->create([
            'user_id' => auth()->id(),
            'session_id' => $this->sessionId,
            'role' => 'user',
            'body' => $validated['message'],
        ]);

        $this->messages[] = [
            'role' => 'user',
            'body' => $validated['message'],
            'tools' => [],
        ];

        $result = $agent->reply($validated['message'], $history, auth()->user());
        $this->lastTools = $result['tools_used'];

        $assistantBody = $result['answer'];

        if ($result['tools_used'] !== []) {
            $toolList = collect($result['tools_used'])
                ->map(fn (string $tool): string => '`'.$tool.'`')
                ->implode(', ');
            $assistantBody .= "\n\n---\n\n_Tools used:_ ".$toolList;
        }

        AssistantMessage::query()->create([
            'user_id' => auth()->id(),
            'session_id' => $this->sessionId,
            'role' => 'assistant',
            'body' => $assistantBody,
        ]);

        $this->messages[] = [
            'role' => 'assistant',
            'body' => $assistantBody,
            'tools' => $result['tools_used'],
        ];

        $this->reset('message');
    }

    public function clearChat(): void
    {
        abort_unless(auth()->user()?->canAccess('admin-agent'), 403);

        AssistantMessage::query()
            ->where('session_id', $this->sessionId)
            ->delete();

        $this->messages = [];
        $this->lastTools = [];
    }

    public function render(): View
    {
        return view('livewire.admin.admin-agent-chat', [
            'suggestions' => [
                'Dashboard summary',
                'Show low stock',
                'Pending orders',
                'List categories',
                'Find customer ',
            ],
        ]);
    }

    private function restoreHistory(): void
    {
        $this->messages = AssistantMessage::query()
            ->where('session_id', $this->sessionId)
            ->orderBy('id')
            ->get()
            ->map(fn (AssistantMessage $message): array => [
                'role' => $message->role,
                'body' => $message->body,
                'tools' => [],
            ])
            ->all();
    }
}
