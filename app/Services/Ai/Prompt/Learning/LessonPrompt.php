<?php namespace App\Services\Ai\Prompt\Learning;

use InvalidArgumentException;
use App\Services\Ai\Prompt\PromptInterface;

class LessonPrompt implements PromptInterface
{
    private $params;

    public function __construct(array $params = [])
    {
        $this->params = new \stdClass();
        
        if (isset($params['questions'])) {
           $this->setQuestions($params['questions']);
        }

        if (isset($params['topic'])) {
           $this->setTopic($params['topic']);
        }
    }

    public function setQuestions(array $questions): LessonPrompt
    {
        $this->params->questions = $questions;
        return $this;
    }

    public function setTopic(string $topic): LessonPrompt
    {
        $this->params->topic = $topic;
        return $this;
    }

    public function getParams(): \stdClass
    {
        // return a clone so that the original object cannot be modified
        return clone $this->params;
    }

    public function getMessages(): array
    {
        $this->validateParams();

        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = $this->getUserPrompt();

        return [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ];
    }


    private function validateParams(): void
    {
        if (!isset($this->params->topic)) {
            throw new InvalidArgumentException('Topic is required (Hint: use the setTopic() method or pass it in the constructor)))');
        }
    }

    public function getSystemPrompt(): string
    {
        $this->validateParams();

        $questionPrompt = '';
        if (isset($this->params->questions)) {
            $questionsString = implode(', ', $this->params->questions);
            $questionPrompt = <<<EOT
 that helps answer the question(s) without directly answering or mentioning them: 
{$questionsString}
EOT;
        }

        return <<<EOT
ou are an AI that generates a single {$this->params->topic} lesson{$questionPrompt}.
Include examples in html format with <pre> tags for code, <p> for text etc...
escape html special characters and encode entities (ex: `<` should be encoded as `&lt;`).
Escape newlines characters and other special characters (ex: `\n` should be encoded as `\\n`).
Title should not use prefix such as "Lesson Topic: " or "Lesson Topic - ".
Lesson must include a short plain text excerpt 1-2 sentences long.
Lesson should be in valid json format:
{
    "title": "(ex: Loops in Python)",
    "excerpt": "plain text",
    "body": "Lesson body html"
}
EOT;
    }

    public function getUserPrompt(): string
    {
        return "Generate a Lesson";
    }
}
