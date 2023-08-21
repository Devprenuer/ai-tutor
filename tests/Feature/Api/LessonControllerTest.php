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

class LessonControllerTest extends TestCase {

    public function test_requires_authentication()
    {
        $this->getJson(route('api.lesson.index'))
            ->assertUnauthorized();
    }

    public function test_fetches_lessons_in_topic_and_difficulty()
    {
        // create a topic
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

        $this->getJson(route('api.lesson.show', $lesson->id));

        $this->assertEquals(1, $lesson->refresh()->view_count);
    }
    
}