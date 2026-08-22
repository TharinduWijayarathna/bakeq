<?php

namespace App\Contracts;

interface CakeKnowledgeAssistant
{
    /**
     * @param  list<array{role: string, body: string}>  $history
     */
    public function reply(string $question, array $history = []): string;
}
