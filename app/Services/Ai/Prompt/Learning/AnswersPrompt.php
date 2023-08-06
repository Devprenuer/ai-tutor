<?php namespace App\Services\Ai\Prompt;

class AnswersPrompt implements PromptInterface
{
    private $question;

    public function __construct(string $question)
    {
        $this->question = $question;
    }

    public function getMessages(): array
    {
        return [
            [
                'role' => 'system',
                'content' => "You are a helpful assistant that generates answers to coding questions."
            ],
            [
                'role' => 'user',
                'content' => "What is the correct answer to the following coding question: '{$this->question}'?"
            ]
        ];
    }
}
