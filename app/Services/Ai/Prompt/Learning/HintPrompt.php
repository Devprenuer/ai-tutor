<?php namespace App\Services\Ai\Prompt;

class HintPrompt implements PromptInterface
{
    private $question;
    private $incorrectAnswer;

    public function __construct(string $question, string $incorrectAnswer)
    {
        $this->question = $question;
        $this->incorrectAnswer = $incorrectAnswer;
    }

    public function getMessages(): array
    {
        return [
            [
                'role' => 'system',
                'content' => "You are a helpful assistant that provides hints to correct incorrect code."
            ],
            [
                'role' => 'user',
                'content' => "The question is: '{$this->question}'. An incorrect answer given is: '{$this->incorrectAnswer}'. What's wrong with this code and how can it be corrected?"
            ]
        ];
    }
}
