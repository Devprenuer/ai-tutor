<?php namespace Test\Feature\Api;

use App\Models\User;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Hint;
use App\Models\UserHintView;
use App\Models\UserQuestionView;
use Tests\TestCase;
use App\Services\Ai\AiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery;

class QuestionControllerTest extends TestCase {

    const TEST_QUESTION = 'What is the meaning of life?';

    const TEST_QUESTION_MULTIPLE_CHOICES = [
        'a' => 1,
        'b' => 2,
        'c' => 3,
        'd' => 4
    ];

    use RefreshDatabase;

    private function mockChat($returning): Mockery\MockInterface {
        // mock Open AI
        return $this->mock(AiClient::class, function ($mock) use ($returning) {
            return $mock->shouldReceive('chat')
                ->once()
                ->andReturn($returning);
        });
    }

    private function fetchQuestionAsUser(User $user, array $params): TestResponse {
        $params = array_merge([
            'page' => 1,
            'mock' => false,
            'dump' => false,
            'question' => (object) Question::factory()->make([
                'question' => self::TEST_QUESTION,
                'question_type' => 0
            ])->toArray()
        ], $params);

        $question = $params['question'];

        if (@$params['dump']) {
            //var_dump($question->difficulty_level, 'expected');
        }

        if ($params['mock']) {
            $this->mockChat($question);
        }
        
        $response = $this->actingAs($user)
            ->getJson("/api/question?topic_id={$question->topic_id}&difficulty_level={$question->difficulty_level}&page={$params['page']}&question_type={$question->question_type}&dump={$params['dump']}");

        return $response;
    }

    private function fetchHintAsUser(User $user, array $params): TestResponse {
        $params = array_merge([
            'question_id' => 1,
            'page' => 1,
            'mock_hints' => []
        ], $params);
        if (!empty($params['mock_hints'])) {
            $this->mockChat($params['mock_hints']);
        }

        $response = $this->actingAs($user)
            ->getJson("/api/question/{$params['question_id']}/hint?page={$params['page']}");

        return $response;
    }

    public function setUp(): void
    {
        putenv('OPENAI_API_KEY=mock_key');
        parent::setUp();
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
        Question::factory()->create([
            'difficulty_level' => 1,
            'topic_id' => $topic->id,
            'question_type' => 0
        ]);

        $expectedQuestion = Question::factory()->make([
            'difficulty_level' => 2,
            'topic_id' => $topic->id,
            'question_type' => 0
        ]);

        $response = $this->fetchQuestionAsUser($user, [
            'question' => $expectedQuestion,
            'mock' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1);

        $question = $response->json('question');
        $this->assertSame($question['question'], $expectedQuestion->question);
        $this->assertSame($question['difficulty_level'], 2);
        $this->assertSame($question['topic_id'], $topic->id);
        $this->assertSame($question['question_type'], 0);
        $this->assertNotNull($question['id']);
    }

    public function test_when_questions_dont_exist_in_topic_of_type_they_are_generated_by_ai(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        Question::factory()->create([
            'difficulty_level' => 2,
            'topic_id' => $topic->id,
            'question_type' => 0
        ]);

        $expectedQuestion = (object) Question::factory()->make([
            'difficulty_level' => 2,
            'topic_id' => $topic->id,
            'question_type' => 1
        ])->toArray();

        $expectedQuestion->options = self::TEST_QUESTION_MULTIPLE_CHOICES;
        $expectedQuestion->answer = 'a';

        $response = $this->fetchQuestionAsUser($user, [
            'question' => $expectedQuestion,
            'mock' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1);

        $question = $response->json('question');
        $this->assertSame($question['question'], $expectedQuestion->question);
        $this->assertSame($question['difficulty_level'], 2);
        $this->assertSame($question['topic_id'], $topic->id);
        $this->assertSame($question['question_type'], 1);
        $this->assertSame($question['multiple_choice_options'], self::TEST_QUESTION_MULTIPLE_CHOICES);
        $this->assertNotNull($question['id']);

        $this->assertDatabaseHas('questions', [
            'id' => $question['id'],
            'question' => $expectedQuestion->question,
            'difficulty_level' => 2,
            'topic_id' => $topic->id,
            'question_type' => 1,
            'multiple_choice_options' => json_encode(self::TEST_QUESTION_MULTIPLE_CHOICES),
            'multiple_choice_answer' => 'a'
        ]);
    }

    public function test_fails_with_500_when_multiple_choice_types_dont_have_options_or_answers(): void
    {
        $user = User::factory()->create();
        $expectedQuestion = (object) Question::factory()->make([
            'difficulty_level' => 2,
            'question_type' => 1
        ])->toArray();

        $response = $this->fetchQuestionAsUser($user, [
            'question' => $expectedQuestion,
            'mock' => true
        ]);

        $response->assertStatus(500);
    }

    public function test_adds_user_view_on_returned_question(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->make([
            'difficulty_level' => 1,
            'question_type' => 0
        ]);

        $response = $this->fetchQuestionAsUser($user, [
            'question' => $question,
            'mock' => true
        ]);

        $question = $response->json('question');

        $this->assertDatabaseHas('user_question_views', [
            'user_id' => $user->id,
            'question_id' => $question['id']
        ]);

        $this->assertEquals(1, Question::find($question['id'])->view_count);
    }

    public function test_fetches_questions_from_database_at_matching_difficulty_and_topic_if_they_exist(): void
    {
        $user = User::factory()->create();
        $expectedQuestion = Question::factory()->create([
            'question_type' => 0,
            'difficulty_level' => 1
        ]);

        $response = $this->fetchQuestionAsUser($user, [
            'question' => $expectedQuestion,
            'mock' => false
        ]);

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
            'topic_id' => $topic->id,
            'question_type' => 0
        ]);

        $questions->each(function ($question) use ($user) {
            $question->addViewByUser($user->id);
        });

        $expectedQuestion = Question::factory()->make([
            'difficulty_level' => $difficultyLevel,
            'topic_id' => $topic->id
        ]);

        $response = $this->fetchQuestionAsUser($user, [
            'question' => (object) $expectedQuestion->toArray(),
            'mock' => true
        ]);

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
            'topic_id' => $topic->id,
            'question_type' => 0
        ]);

        $response = $this->fetchQuestionAsUser($user, [
            'question' => (object) [
                'difficulty_level' => $difficultyLevel,
                'topic_id' => $topic->id,
                'question_type' => 0
            ],
            'page' => 2
        ]);
        $question = $response->json('question');
        $expectedQuestion = Question::query()
            ->orderBy('created_at', 'desc')
            ->get()[1];
        $this->assertSame($question['id'], $expectedQuestion['id']);
    }

    public function test_question_hints_from_database_when_exist(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $difficultyLevel = 1;
        $question = Question::factory()->create([
            'difficulty_level' => $difficultyLevel,
            'topic_id' => $topic->id,
            'question_type' => 0
        ]);

        $hints = [];
        
        for ($i = 0; $i < 10; $i++) {
            $hints[] = Hint::factory()->create([
                'question_id' => $question->id,
                'helpfulness_level' => $i + 1
            ]);
        }

        $response = $this->fetchHintAsUser($user, [
            'question_id' => $question->id,
            'page' => 2
        ]);

        $hint = $response->json('hint');

        $this->assertEquals($hints[1]->id, $hint['id']);
    
        $response = $this->fetchHintAsUser($user, [
            'question_id' => $question->id,
            'page' => 3
        ]);
        $hint = $response->json('hint');

        $this->assertEquals($hints[2]->id, $hint['id']);
    }

    public function test_generates_new_hints_using_ai_if_none_in_db_on_the_first_request_and_returns_given_page(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create([
            'question_type' => 0
        ]);

        $hints = [];
        
        for ($i = 0; $i < 10; $i++) {
            $hints[] = Hint::factory()->make([
                'question_id' => $question->id,
                'helpfulness_level' => $i + 1
            ]);
        }

        for ($i = 2; $i <= 10; $i++) {
            // only mock hints on the first page
            // subsequent pages will be loaded from the database
            // start from page two to validate that the page number is being used
            // on the first request
            $hints = $i === 2 ? $hints : [];
            $response = $this->fetchHintAsUser($user, [
                'question_id' => $question->id,
                'page' => $i,
                'mock_hints' => $hints
            ]);
            $hint = $response->json('hint');

            $firstHint = Hint::where('question_id', $question->id)
                ->skip($i - 1)
                ->take(1)
                ->orderBy('created_at', 'asc')
                ->orderBy('helpfulness_level', 'asc')
                ->get()
                ->first();
            
            $this->assertEquals($firstHint->id, $hint['id']);
        }
    }


    public function test_question_hints_adds_user_view_on_request(): void
    {
        $user = User::factory()->create();

        $hint = Hint::factory()->create();

        $this->assertDatabaseMissing('user_hint_views', [
            'user_id' => $user->id,
            'hint_id' => $hint->id
        ]);

        $this->assertEquals(0, Hint::find($hint->id)->view_count);

        $response = $this->fetchHintAsUser($user, [
            'question_id' => $hint->question_id,
            'page' => 1
        ]);
        
        $hint = Hint::first();
        $this->assertDatabaseHas('user_hint_views', [
            'user_id' => $user->id,
            'hint_id' => $hint->id
        ]);

        $this->assertEquals(1, Hint::find($hint->id)->view_count);
    }
    
}