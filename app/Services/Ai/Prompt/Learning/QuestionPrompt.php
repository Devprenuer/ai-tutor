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

        if (isset($params['question_type'])) {
            $this->setQuestionType($params['question_type']);
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

        if (!isset($this->params->question_type)) {
            throw new InvalidArgumentException('question_type is required (Hint: use the setQuestionType() method or pass it in the constructor)))');
        }
    }

    public function setPreviousQuestions(array $previousQuestions): QuestionPrompt
    {
        $this->params->previousQuestions = $previousQuestions;
        return $this;
    }

    public function setQuestionType(string $questionType): QuestionPrompt
    {
        $this->params->question_type = $questionType;
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
Please generate a difficulty level {$this->params->difficulty_level} {$this->params->question_type}
{$this->params->industry} question about {$this->params->topic}.
EOT;
    }

    public function getSystemPrompt(): string
    {
        $this->validateParams();

        return <<<EOT
You are a json api that generates {$this->params->industry} questions
at difficulty range 1 to 10. 10 most difficult.
Your response matches the schema below. Ex:
user: "{$this->getUserPrompt()}"
you: "{$this->getSystemExampleJsonResponse()}"
user: "{$this->getUserNextQuestionPrompt()}"
you: (new question same difficulty)
EOT;
    }

    public function getSystemExampleJsonResponse(): string
    {
        $json = [
            'question' => '...',
            'difficulty_level' => $this->params->difficulty_level
        ];

        if ($this->params->question_type === 'Multiple Choice') {
            $json['options'] = [
                'a' => '...',
                'b' => '...',
                'c' => '...',
                'd' => '...'
            ];
            $json['answer'] = 'a';
        }

        return json_encode($json);
    }

    public function getUserNextQuestionPrompt(): string
    {
        return "next";
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

                $questionJson = [
                    'question' => $previousQuestion['question'],
                    'difficulty_level' => $previousQuestion['difficulty_level']
                ];

                if (isset($previousQuestion['multiple_choice_options']) && $previousQuestion['multiple_choice_options']) {
                    $questionJson['options'] = $previousQuestion['multiple_choice_options'];
                    $questionJson['answer'] = $previousQuestion['multiple_choice_answer'];
                }

                $previousQuestionMessages[] = [
                    'role' => 'assistant',
                    'content' => json_encode($questionJson)
                ];

                $i++;
            }

            return $previousQuestionMessages;
        }

        return [];
    }
}