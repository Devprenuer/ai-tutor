<?php namespace App\Services\Ai\Prompt\Learning;

use InvalidArgumentException;
use App\Services\Ai\Prompt\PromptInterface;

class QuestionPrompt implements PromptInterface
{
    private $params;

    public function __construct(array $params = [])
    {
        $this->params = new \stdClass();
        
        if (isset($params['topic'])) {
           $this->setTopic($params['topic']);
        }

        if (isset($params['difficulty_level'])) {
           $this->setDifficultyLevel($params['difficulty_level']);
        }

        if (isset($params['industry'])) {
           $this->setIndustry($params['industry']);
        }

        if (isset($params['previous_questions'])) {
           $this->setPreviousQuestions($params['previous_questions']);
        }
    }

    private function validateParams(): void
    {
        if (!isset($this->params->topic)) {
            throw new InvalidArgumentException('Topic is required (Hint: use the setTopic() method or pass it in the constructor)))');
        }

        if (!isset($this->params->difficulty_level)) {
            throw new InvalidArgumentException('difficulty_level is required (Hint: use the setDifficultyLevel() method or pass it in the constructor)))');
        }

        if (!isset($this->params->industry)) {
            throw new InvalidArgumentException('Industry is required (Hint: use the setIndustry() method or pass it in the constructor)))');
        }
    }

    public function setPreviousQuestions(array $previousQuestions): QuestionPrompt
    {
        $this->params->previousQuestions = $previousQuestions;
        return $this;
    }

    public function setTopic(string $topic): QuestionPrompt
    {
        $this->params->topic = $topic;
        return $this;
    }

    public function setDifficultyLevel(int $difficulty_level): QuestionPrompt
    {
        $this->params->difficulty_level = $difficulty_level;
        return $this;
    }

    public function setIndustry(string $industry): QuestionPrompt
    {
        $this->params->industry = $industry;
        return $this;
    }

    public function getParams(): \stdClass
    {
        // return a clone so that the original object cannot be modified
        return clone $this->params;
    }

    public function getMessages(): array
    {
        $chatHistoryMessages = $this->getChatHistoryMessages();

        $userPrompt = empty($chatHistoryMessages) ?
            $this->getUserPrompt() : $this->getUserNextQuestionPrompt();

        $systemPrompt = $this->getSystemPrompt();

        return [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            ...$chatHistoryMessages,
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ];
    }

    public function getUserPrompt(): string
    {
        $this->validateParams();

        return <<<EOT
Please generate a {$this->params->difficulty_level} level {$this->params->industry} question about {$this->params->topic}.
EOT;
    }

    public function getSystemPrompt(): string
    {
        $this->validateParams();

        return <<<EOT
You are a chat assistant api that generates {$this->params->industry} questions
at incresing levels of difficulty_level ranging from 1 to 10. 10 being the most difficult.
You return questions in json format where the `question` key contains the question
the `difficulty_level` key contains the difficulty_level level and the `topic` key contains the topic.
For example, if the user prompt was: "{$this->getUserPrompt()}" the system prompt would be
something like this:
{
    "question": "...",
    "difficulty_level": {$this->params->difficulty_level},
    "topic": "{$this->params->topic}"
}
Even though you accept user prompts in plain text, you return questions in json format. If the user
prompt is "next" you return a new question.
EOT;
    }

    public function getUserNextQuestionPrompt(): string
    {
        return "next question, same topic and difficulty_level";
    }

    public function getChatHistoryMessages(): array
    {
        $previousQuestionMessages = [];
        $userPrompt = $this->getUserPrompt();
        if (isset($this->params->previousQuestions)) {
            $i = 0;
            foreach ($this->params->previousQuestions as $previousQuestion) {
                if ($i > 0) {
                    $userPrompt = $this->getUserNextQuestionPrompt();
                }

                $previousQuestionMessages[] = [
                    'role' => 'user',
                    'content' => $userPrompt
                ];

                $previousQuestionMessages[] = [
                    'role' => 'assistant',
                    'content' => <<<EOT
{
    "question": "{$previousQuestion['question']}",
    "difficulty_level": {$previousQuestion['difficulty_level']},
    "topic": "{$this->params->topic}"
}
EOT
                ];

                $i++;
            }
        }
        return $previousQuestionMessages;
    }
}


