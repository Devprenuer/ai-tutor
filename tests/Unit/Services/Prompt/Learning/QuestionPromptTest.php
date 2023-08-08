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
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
                ]
            ],
        ]);

        $params = $qp->getParams();
        $this->assertEquals('PHP', $params->topic);
        $this->assertEquals(1, $params->difficulty_level);
        $this->assertEquals('Web Development', $params->industry);
        $this->assertEquals([
            [
                'question' => 'What is a variable?',
                'topic' => 'PHP',
                'difficulty_level' => 1
            ]
        ], $params->previousQuestions);
    }

    public function test_params_populated_through_setters(): void
    {
        $qp = new QuestionPrompt();
        $qp->setTopic('PHP');
        $qp->setDifficultyLevel(1);
        $qp->setIndustry('Web Development');
        $qp->setPreviousQuestions([
            [
                'question' => 'What is a variable?',
                'topic' => 'PHP',
                'difficulty_level' => 1
            ]
        ]);

        $params = $qp->getParams();
        $this->assertEquals('PHP', $params->topic);
        $this->assertEquals(1, $params->difficulty_level);
        $this->assertEquals('Web Development', $params->industry);
        $this->assertEquals([
            [
                'question' => 'What is a variable?',
                'topic' => 'PHP',
                'difficulty_level' => 1
            ]
        ], $params->previousQuestions);
    }

    public function test_exception_thrown_on_get_messages_when_topic_not_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Topic is required (Hint: use the setTopic() method or pass it in the constructor)))');

        $qp = new QuestionPrompt([
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
                ]
            ],
        ]);

        $qp->getMessages();
    }

    public function test_exception_thrown_on_get_messages_when_difficulty_level_not_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('difficulty_level is required (Hint: use the setDifficultyLevel() method or pass it in the constructor)))');

        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
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
            'difficulty_level' => 1,
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
                ]
            ],
        ]);

        $qp->getMessages();
    }

    public function test_get_chat_history_returned_with_previous_questions(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
                ],
                [
                    'question' => 'What is a class?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
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
{
    "question": "What is a variable?",
    "difficulty_level": 1,
    "topic": "PHP"
}
EOT
            ],
            [
                'role' => 'user',
                'content' => $qp->getUserNextQuestionPrompt()
            ],
            [
                'role' => 'assistant',
                'content' => <<<EOT
{
    "question": "What is a class?",
    "difficulty_level": 1,
    "topic": "PHP"
}
EOT
            ],
        ], $chatHistory);
    }

    public function test_messages_returned(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
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
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1
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
