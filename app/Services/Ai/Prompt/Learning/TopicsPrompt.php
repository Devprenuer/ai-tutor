<?php namespace App\Services\Ai\Prompt;

class TopicsPrompt implements PromptInterface
{
    public function getMessages(): array
    {
        return [
            [
                'role' => 'system',
                'content' => "You are a knowledgeable assistant with a vast understanding of coding topics."
            ],
            [
                'role' => 'user',
                'content' => "What are some potential topics for coding questions?"
            ]
        ];
    }
}
