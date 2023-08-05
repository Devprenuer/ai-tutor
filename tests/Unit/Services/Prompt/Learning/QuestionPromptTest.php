<?php

namespace Tests\Unit\Services\Prompt\Learning;

use PHPUnit\Framework\TestCase;
use App\Services\Ai\Prompt\Learning\QuestionPrompt;

class QuestionPromptTest extends TestCase
{
    /**
     * Expected params get populated.
     */
    public function test_params_populated_through_constructor(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty' => 1
                ]
            ],
        ]);

        $params = $qp->getParams();
        $this->assertEquals('PHP', $params->topic);
        $this->assertEquals(1, $params->difficulty);
        $this->assertEquals('Web Development', $params->industry);
        $this->assertEquals([
            [
                'question' => 'What is a variable?',
                'topic' => 'PHP',
                'difficulty' => 1
            ]
        ], $params->previousQuestions);
    }

    public function test_params_populated_through_setters(): void
    {
        $qp = new QuestionPrompt();
        $qp->setTopic('PHP');
        $qp->setDifficulty(1);
        $qp->setIndustry('Web Development');
        $qp->setPreviousQuestions([
            [
                'question' => 'What is a variable?',
                'topic' => 'PHP',
                'difficulty' => 1
            ]
        ]);

        $params = $qp->getParams();
        $this->assertEquals('PHP', $params->topic);
        $this->assertEquals(1, $params->difficulty);
        $this->assertEquals('Web Development', $params->industry);
        $this->assertEquals([
            [
                'question' => 'What is a variable?',
                'topic' => 'PHP',
                'difficulty' => 1
            ]
        ], $params->previousQuestions);
    }

    public function test_exception_thrown_on_get_messages_when_topic_not_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Topic is required (Hint: use the setTopic() method or pass it in the constructor)))');

        $qp = new QuestionPrompt([
            'difficulty' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty' => 1
                ]
            ],
        ]);

        $qp->getMessages();
    }

    public function test_exception_thrown_on_get_messages_when_difficulty_not_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Difficulty is required (Hint: use the setDifficulty() method or pass it in the constructor)))');

        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty' => 1
                ]
            ],
        ]);

        $qp->getMessages();
    }

    public function test_exception_thrown_on_get_messages_when_industry_not_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Industry is required (Hint: use the setIndustry() method or pass it in the constructor)))');

        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty' => 1,
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty' => 1
                ]
            ],
        ]);

        $qp->getMessages();
    }

    public function test_get_chat_history_returned_with_previous_questions(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty' => 1
                ],
                [
                    'question' => 'What is a class?',
                    'topic' => 'PHP',
                    'difficulty' => 1
                ]
            ],
        ]);

        $chatHistory = $qp->getChatHistoryMessages();
        $this->assertEquals([
            [
                'role' => 'user',
                'content' => $qp->getUserPrompt()
            ],
            [
                'role' => 'assistant',
                'content' => <<<EOT
QUESTION:{
    "question": "What is a variable?",
    "difficulty": 1,
    "topic": "PHP"
}.END
EOT
            ],
            [
                'role' => 'user',
                'content' => $qp->getUserNextQuestionPrompt()
            ],
            [
                'role' => 'assistant',
                'content' => <<<EOT
QUESTION:{
    "question": "What is a class?",
    "difficulty": 1,
    "topic": "PHP"
}.END
EOT
            ],
        ], $chatHistory);
    }

    public function test_messages_returned(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty' => 1,
            'industry' => 'Web Development',
        ]);

        $messages = $qp->getMessages();
        $this->assertEquals([
            [
                'role' => 'system',
                'content' => $qp->getSystemPrompt()
            ],
            [
                'role' => 'user',
                'content' => $qp->getUserPrompt()
            ]
        ], $messages);
    }

    public function test_messages_returned_with_chat_history(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty' => 1
                ]
            ],
        ]);

        $messages = $qp->getMessages();
        $this->assertEquals([
            [
                'role' => 'system',
                'content' => $qp->getSystemPrompt()
            ],
            ...$qp->getChatHistoryMessages(),
            [
                'role' => 'user',
                'content' => $qp->getUserNextQuestionPrompt()
            ]
        ], $messages);
    }
}
