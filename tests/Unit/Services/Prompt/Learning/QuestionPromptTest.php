<?php

namespace Tests\Unit\Services\Prompt\Learning;

use PHPUnit\Framework\TestCase;
use App\Services\Ai\Prompt\Learning\QuestionPrompt;
use App\Models\Question;

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
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
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
        $qp->setQuestionType(Question::QUESTION_TYPES['CODING']['TITLE']);
        $qp->setPreviousQuestions([
            [
                'question' => 'What is a variable?',
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
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
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
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
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
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
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

    public function test_exception_thrown_on_get_messages_when_question_type_not_set(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('question_type is required (Hint: use the setQuestionType() method or pass it in the constructor)))');

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

        $qp->getMessages();
    }

    public function test_get_chat_history_returned_with_previous_questions(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
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
                'content' => json_encode([
                    'question' => 'What is a variable?',
                    'difficulty_level' => 1
                ])
            ],
            [
                'role' => 'user',
                'content' => $qp->getUserNextQuestionPrompt()
            ],
            [
                'role' => 'assistant',
                'content' => json_encode([
                    'question' => 'What is a class?',
                    'difficulty_level' => 1
                ])
            ],
        ], $chatHistory);
    }

    public function test_get_chat_history_previous_questions_includes_multiple_choice_options(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['MULTIPLE_CHOICE']['TITLE'],
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1,
                    'multiple_choice_options' => [
                        'a' => 'A variable is a container for a value.',
                        'b' => 'A variable is a container for a function.',
                        'c' => 'A variable is a container for a class.',
                        'd' => 'A variable is a container for a namespace.',
                    ],
                    'multiple_choice_answer' => 'a'
                ],
                [
                    'question' => 'What is a class?',
                    'topic' => 'PHP',
                    'difficulty_level' => 1,
                    'multiple_choice_options' => [
                        'a' => 'A class is a container for a value.',
                        'b' => 'A class is a container for a function.',
                        'c' => 'A class is a container for a class.',
                        'd' => 'A class is a container for a namespace.',
                    ],
                    'multiple_choice_answer' => 'c'
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
                'content' => json_encode([
                    'question' => 'What is a variable?',
                    'difficulty_level' => 1,
                    'options' => [
                        'a' => 'A variable is a container for a value.',
                        'b' => 'A variable is a container for a function.',
                        'c' => 'A variable is a container for a class.',
                        'd' => 'A variable is a container for a namespace.',
                    ],
                    'answer' => 'a'
                ])
            ],
            [
                'role' => 'user',
                'content' => $qp->getUserNextQuestionPrompt()
            ],
            [
                'role' => 'assistant',
                'content' => json_encode([
                    'question' => 'What is a class?',
                    'difficulty_level' => 1,
                    'options' => [
                        'a' => 'A class is a container for a value.',
                        'b' => 'A class is a container for a function.',
                        'c' => 'A class is a container for a class.',
                        'd' => 'A class is a container for a namespace.',
                    ],
                    'answer' => 'c'
                ])
            ],
        ], $chatHistory);
    }

    public function test_system_example_json_response_returns_expected_content(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
        ]);

        $expected = json_encode([
            'question' => '...',
            'difficulty_level' => 1
        ]);

        $this->assertEquals(
            $expected, $qp->getSystemExampleJsonResponse()
        );
    }

    public function test_system_example_json_response_returns_expected_content_with_multiple_choice(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['MULTIPLE_CHOICE']['TITLE'],
        ]);

        $expected = json_encode([
            'question' => '...',
            'difficulty_level' => 1,
            'options' => [
                'a' => '...',
                'b' => '...',
                'c' => '...',
                'd' => '...',
            ],
            'answer' => 'a'
        ]);

        $this->assertEquals(
            $expected, $qp->getSystemExampleJsonResponse()
        );
    }

    public function test_user_prompts_contain_expected_text(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
        ]);

        $expected = <<<EOT
Please generate a difficulty level 1 Coding
Web Development question about PHP.
EOT;
        $this->assertEquals(
            $expected, $qp->getUserPrompt()
        );

        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['MULTIPLE_CHOICE']['TITLE'],
        ]);

        $expected = <<<EOT
Please generate a difficulty level 1 Multiple Choice
Web Development question about PHP.
EOT;
        $this->assertEquals(
            $expected, $qp->getUserPrompt()
        );

        $this->assertEquals(
            'next', $qp->getUserNextQuestionPrompt()
        );
    }


    public function test_system_prompt_returns_expected_content(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
        ]);

        $expected = <<<EOT
You are a json api that generates Web Development questions
at difficulty range 1 to 10. 10 most difficult.
Your response matches the schema below. Ex:
user: "{$qp->getUserPrompt()}"
you: "{$qp->getSystemExampleJsonResponse()}"
user: "{$qp->getUserNextQuestionPrompt()}"
you: (new question same difficulty)
EOT;

        $this->assertEquals(
           $expected, $qp->getSystemPrompt()
        );
    }

    public function test_messages_returned(): void
    {
        $qp = new QuestionPrompt([
            'topic' => 'PHP',
            'difficulty_level' => 1,
            'industry' => 'Web Development',
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
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
            'question_type' => Question::QUESTION_TYPES['CODING']['TITLE'],
            'previous_questions' => [
                [
                    'question' => 'What is a variable?',
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
