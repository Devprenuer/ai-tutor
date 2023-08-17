<?php namespace Test\Feature\Api;

use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use Tests\TestCase;
use App\Services\Ai\AiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Carbon\Carbon;
use Mockery;

class AnswerControllerTest extends TestCase {

    const TEST_QUESTION = 'What is the meaning of life?';

    const TEST_QUESTION_MULTIPLE_CHOICES = [
        'a' => 1,
        'b' => 2,
        'c' => 3,
        'd' => 4
    ];

    use RefreshDatabase;

    public function test_answering_nonexistent_question_gives_404(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/question/doesnt_exist/answer', [
                'answer' => 'a'
            ]
        );

        $response->assertStatus(404);
    }

    public function test_user_cant_answer_question_they_havent_viewed(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()
            ->multipleChoice()
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson("/api/question/{$question->id}/answer", [
                'answer' => 'a'
            ]
        );

        $response->assertStatus(403);
        $this->assertEquals(
            'You must view the question before answering it',
            $response->json('message')
        );
    }

    public function test_correct_answered_question_updates_database(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()
            ->multipleChoice()
            ->create();

        Carbon::setTestNow(
            Carbon::create(2023, 1, 1, 12, 0, 0)
        );

        $question->addViewByUser($user->id);

        Carbon::setTestNow(
            Carbon::create(2023, 1, 1, 13, 0, 0)
        );

        $response = $this
            ->actingAs($user)
            ->postJson("/api/question/{$question->id}/answer", [
                'answer' => $question->multiple_choice_answer
            ]
        );

        $response->assertStatus(200);
        $this->assertEquals(
            'Question answered successfully',
            $response->json('message')
        );

        $answer = Answer::where([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'answer' => $question->multiple_choice_answer,
            'score' => 10,
            'time_taken' => 3600
        ])->first();

        $this->assertNotNull($answer);

        $answerResponse = $response->json('answer');

        $this->assertEquals(
            $answer->id,
            $answerResponse['id']
        );

        $this->assertEquals(
            $answer->answer,
            $answerResponse['answer']
        );

        $this->assertEquals(
            $answer->score,
            $answerResponse['score']
        );

        $this->assertEquals(
            $answer->time_taken,
            $answerResponse['time_taken']
        );

        $this->assertNull(
            @$answerResponse['question_id']
        );

        $question->refresh();

        $this->assertEquals(
            $question->answers_count,
            1
        );

        $user->refresh();

        $this->assertEquals(
            $user->questions_answered_count,
            1
        );
    }

    public function test_incorrect_answered_question_updates_database(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()
            ->multipleChoice()
            ->create();
        
        Carbon::setTestNow(
            Carbon::create(2023, 1, 1, 12, 0, 0)
        );

        $question->addViewByUser($user->id);

        Carbon::setTestNow(
            Carbon::create(2023, 1, 1, 13, 0, 0)
        );

        $wrongAnswer = $question->multiple_choice_answer === 'a' ? 'b' : 'a';

        $response = $this
            ->actingAs($user)
            ->postJson("/api/question/{$question->id}/answer", [
                'answer' => $wrongAnswer
            ]
        );

        $response->assertStatus(200);
        $this->assertEquals(
            'Question answered successfully',
            $response->json('message')
        );

        $answer = Answer::where([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'answer' => $wrongAnswer,
            'score' => 0,
            'time_taken' => 3600
        ])->first();

        $this->assertNotNull($answer);
        $answerResponse = $response->json('answer');

        $this->assertEquals(
            $answer->id,
            $answerResponse['id']
        );

        $this->assertEquals(
            $answer->answer,
            $answerResponse['answer']
        );

        $this->assertEquals(
            $answer->score,
            $answerResponse['score']
        );

        $this->assertEquals(
            $answer->time_taken,
            $answerResponse['time_taken']
        );

        $this->assertNull(
            @$answerResponse['question_id']
        );

        $question->refresh();

        $this->assertEquals(
            1,
            $question->answers_count
        );

        $user->refresh();

        $this->assertEquals(
            $user->questions_answered_count,
            1
        );
    }

    public function test_user_can_only_answer_multiple_choice_questions(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()
            ->create([
                'question_type' => Question::QUESTION_TYPES['CODING']['ID']
            ]);
        
        $question->addViewByUser($user->id);

        $response = $this
            ->actingAs($user)
            ->postJson("/api/question/{$question->id}/answer", [
                'answer' => 'a'
            ]
        );

        $response->assertStatus(403);
        $this->assertEquals(
            'Not implemented',
            $response->json('message')
        );
    }   
}