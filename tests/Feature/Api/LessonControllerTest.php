<?php namespace Test\Feature\Api;

use App\Models\User;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\UserLessonView;
use Tests\TestCase;
use App\Services\Ai\AiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery;

class LessonControllerTest extends TestCase {

    use RefreshDatabase;

    public function test_requires_authentication()
    {
        $this->getJson(route('api.lesson.index'))
            ->assertUnauthorized();
    }

    public function test_fetches_lessons_in_topic_and_difficulty()
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();

        $lessons = Lesson::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'difficulty_level' => 1,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.index', [
                'topic_id' => $topic->id,
                'difficulty' => 1,
            ]));

        $response->assertOk();

        $jsonLessons = $response->json('lessons');

        $this->assertCount(3, $jsonLessons);
        $this->assertEquals($lessons->pluck('id')->toArray(), array_column($jsonLessons, 'id'));
    }

    public function test_fetches_lessons_in_topic_and_difficulty_with_pagination()
    {
        // create a topic
    }

    public function test_fetches_lessons_in_topic_and_difficulty_with_pagination_and_sorting()
    {
        // create a topic
    }

    public function test_fetches_lessons_in_topic_and_difficulty_with_pagination_and_sorting_direction()
    {
        // create a topic
    }

    public function test_fetches_lessons_in_topic_and_difficulty_with_pagination_and_sorting_direction_and_search()
    {
        // create a topic
    }

    public function test_fetches_lessons_with_growing_difficulty()
    {
        // create a topic
    }

    public function test_single_lesson_includes_body()
    {
        // create a topic
    }

    public function test_single_lesson_includes_prev_and_next_links()
    {
        // create a topic
    }

    public function test_lesson_adds_view_count()
    {
        $lesson = Lesson::factory()->create();

        $this->assertEquals(0, $lesson->view_count);

        $user = User::factory()->create();
        $this
            ->actingAs($user)
            ->getJson(route('api.lesson.show', $lesson->id));

        $this->assertEquals(1, $lesson->refresh()->view_count);
    }
    
}