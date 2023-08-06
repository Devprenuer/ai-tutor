<?php namespace Test\Feature\Api;

use App\Models\User;
use App\Models\Topic;
use App\Models\Question;
use App\Models\UserQuestionView;
use Tests\TestCase;
use App\Services\Ai\AiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery;

class QuestionControllerTest extends TestCase {

    const TEST_QUESTION = 'What is the meaning of life?';

    use RefreshDatabase;

    private function mockChat(array $returning): Mockery\MockInterface {
        // mock Open AI
        return $this->mock(AiClient::class, function ($mock) use ($returning) {
            return $mock->shouldReceive('chat')
                ->once()
                ->andReturn((object) $returning);
        });
    }

    private function fetchQuestionAsUser(User $user, Topic $topic, int $difficultyLevel, int $page = null, bool $mock = true): TestResponse {
        $expectedQuestion = [
            'question' => self::TEST_QUESTION,
            'difficulty_level' => $difficultyLevel,
            'topic_id' => $topic->id
        ];

        if ($mock) {
            $this->mockChat($expectedQuestion);
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/question?topic_id={$topic->id}&difficulty_level={$difficultyLevel}&page={$page}");

        return $response;
    }

    public function test_fetching_question_requires_authentication(): void
    {
        $response = $this->getJson('/api/question?topic_id=1&difficulty_level=1');

        $response->assertStatus(401);
    }

    public function test_when_questions_dont_exist_in_topic_at_diff_level_they_are_generated_by_ai(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $difficultyLevel = 1;

        $response = $this->fetchQuestionAsUser($user, $topic, $difficultyLevel);

        $response->assertStatus(200);
        $response->assertJsonCount(1);

        $question = $response->json('question');
        $this->assertSame($question['question'], self::TEST_QUESTION);
        $this->assertSame($question['difficulty_level'], $difficultyLevel);
        $this->assertSame($question['topic_id'], $topic->id);
        $this->assertNotNull($question['id']);
    }

    public function test_adds_user_view_on_returned_question(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $difficultyLevel = 1;

        $response = $this->fetchQuestionAsUser($user, $topic, $difficultyLevel, null, false);
        $question = $response->json('question');

        $this->assertDatabaseHas('user_question_views', [
            'user_id' => $user->id,
            'question_id' => $question['id']
        ]);
    }

    public function test_fetches_questions_from_database_at_matching_difficulty_and_topic_if_they_exist(): void
    {
        $user = User::factory()->create();
        $expectedQuestion = Question::factory()->create();
        $topic = $expectedQuestion->topic;

        $difficultyLevel = $expectedQuestion->difficulty_level;

        $response = $this->fetchQuestionAsUser($user, $topic, $difficultyLevel, null, false);
        $question = $response->json('question');

        $this->assertSame($question['question'], $expectedQuestion['question']);
        $this->assertSame($question['difficulty_level'], $expectedQuestion['difficulty_level']);
        $this->assertSame($question['topic_id'], $expectedQuestion['topic_id']);
        $this->assertNotNull($question['id']);
    }

    public function test_generates_new_question_if_all_matching_questions_were_previously_viewed_by_user(): void
    {
        $topic = Topic::factory()->create();
        $difficultyLevel = 1;
        $user = User::factory()->create();

        $questions = Question::factory(3)->create([
            'difficulty_level' => $difficultyLevel,
            'topic_id' => $topic->id
        ]);

        $questions->each(function ($question) use ($user) {
            $question->addViewByUser($user->id);
        });

        $expectedQuestion = [
            'question' => self::TEST_QUESTION,
            'difficulty_level' => $difficultyLevel,
            'topic_id' => $topic->id
        ];

        $response = $this->fetchQuestionAsUser($user, $topic, $difficultyLevel);
        $question = $response->json('question');

        // assert that the question returned is not in the original array of questions
        $this->assertNotContains($question['id'], $questions->pluck('id')->toArray());
        $this->assertSame($question['question'], $expectedQuestion['question']);
        $this->assertSame($question['difficulty_level'], $expectedQuestion['difficulty_level']);
        $this->assertSame($question['topic_id'], $expectedQuestion['topic_id']);
        $this->assertNotNull($question['id']);
    }

    public function test_question_is_paginated_by_1(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $difficultyLevel = 1;
        $questions = Question::factory(4)->create([
            'difficulty_level' => $difficultyLevel,
            'topic_id' => $topic->id
        ]);

        $response = $this->fetchQuestionAsUser($user, $topic, $difficultyLevel, 2, false);
        $question = $response->json('question');
        $expectedQuestion = Question::query()
            ->orderBy('created_at', 'desc')
            ->get()[1];
        $this->assertSame($question['id'], $expectedQuestion['id']);
    }
    
}