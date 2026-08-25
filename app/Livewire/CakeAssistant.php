<?php

namespace App\Livewire;

use App\Contracts\CakeKnowledgeAssistant;
use App\Models\AssistantMessage;
use App\Support\AssistantTools;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Cake questions')]
class CakeAssistant extends Component
{
    public string $message = '';

    #[Locked]
    public string $sessionId = '';

    #[Locked]
    public string $question = '';

    #[Locked]
    public string $answer = '';

    public function mount(): void
    {
        $this->sessionId = 'user-'.auth()->id();
        $this->restoreLatestExchange();
    }

    public function askSuggestion(string $question, CakeKnowledgeAssistant $assistant): void
    {
        $this->message = $question;
        $this->ask($assistant);
    }

    public function ask(CakeKnowledgeAssistant $assistant): void
    {
        $validated = $this->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $history = AssistantMessage::query()
            ->where('session_id', $this->sessionId)
            ->orderBy('id')
            ->get()
            ->map(fn (AssistantMessage $message): array => [
                'role' => $message->role,
                'body' => $message->body,
            ])
            ->all();

        AssistantMessage::query()->create([
            'user_id' => auth()->id(),
            'session_id' => $this->sessionId,
            'role' => 'user',
            'body' => $validated['message'],
        ]);

        $this->question = $validated['message'];

        $toolResult = AssistantTools::tryHandle($validated['message'], auth()->user());

        $this->answer = $toolResult['handled']
            ? (string) $toolResult['answer']
            : $assistant->reply($validated['message'], $history);

        AssistantMessage::query()->create([
            'user_id' => auth()->id(),
            'session_id' => $this->sessionId,
            'role' => 'assistant',
            'body' => $this->answer,
        ]);

        $this->reset('message');
    }

    public function render(): View
    {
        return view('livewire.cake-assistant', [
            'suggestions' => [
                'Status of order #1',
                'Recommend a birthday cake under 8000',
                'How should I store a cream cake?',
                'How does the designer work?',
            ],
            'whatsappUrl' => AssistantTools::whatsappHandoffUrl(
                'Hi Bakeq, I need help with a cake order.'
            ),
        ]);
    }

    private function restoreLatestExchange(): void
    {
        $latestAnswer = AssistantMessage::query()
            ->where('session_id', $this->sessionId)
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        if ($latestAnswer === null) {
            return;
        }

        $latestQuestion = AssistantMessage::query()
            ->where('session_id', $this->sessionId)
            ->where('role', 'user')
            ->where('id', '<', $latestAnswer->id)
            ->latest('id')
            ->first();

        $this->answer = $latestAnswer->body;
        $this->question = $latestQuestion?->body ?? '';
    }
}
