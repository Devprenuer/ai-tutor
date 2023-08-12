<?php namespace App\Services\Ai\Prompt\Learning;

use InvalidArgumentException;
use App\Services\Ai\Prompt\PromptInterface;

class HintsPrompt implements PromptInterface
{
    private $params;

    public function __construct(array $params = [])
    {
        $this->params = new \stdClass();
        
        if (isset($params['question'])) {
           $this->setQuestion($params['question']);
        }

        if (isset($params['previous_hints'])) {
           $this->setPreviousHints($params['previous_hints']);
        }
    }

    private function validateParams(): void
    {
        if (!isset($this->params->question)) {
            throw new InvalidArgumentException('Question is required (Hint: use the setQuestion() method or pass it in the constructor)))');
        }
    }

    public function setPreviousHints(array $previousHints): HintsPrompt
    {
        $this->params->previousHints = $previousHints;
        return $this;
    }

    public function setQuestion(string $question): HintsPrompt
    {
        $this->params->question = $question;
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

        $userPrompt = $this->getUserPrompt();

        $systemPrompt = $this->getSystemPrompt();

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

    public function getUserPrompt(): string
    {
        return "Please generate at least 10 hints for the question '{$this->params->question}' with helpfulness levels from 1 to 10.";
    }

    public function getSystemPrompt(): string
    {
        return <<<EOT
You are a chat assistant that generates hints for given questions.
The hints have a helpfulness level from 1 to 10, with 1 adding more clarity to the question
and 10 almost answering the question. Each hint will include an example. Any code included in the hint
should be valid code, wrapped in a code block. e.g. ```code goes here``` 
The hints should be returned in JSON format as 
an array of objects example: ```[
    {"hint": "hint 1", "helpfulness_level": 1},
    {"hint": "hint 2", "helpfulness_level": 2}
]
```   
EOT;
    }
}
