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
        $topics = Topic::factory()->count(2)->create();
        $topic = $topics[0];
        $topic2 = $topics[1];
    
        $lessons = Lesson::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'difficulty_level' => 1,
        ]);

        // create some lessons in a different topic
        // to make sure they are not included in the response
        Lesson::factory()->count(2)->create([
            'topic_id' => $topic2->id,
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
        $user = User::factory()->create();
        $topic = Topic::factory()->create();

        $lessons = Lesson::factory()->count(19)->create([
            'topic_id' => $topic->id,
            'difficulty_level' => 2,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.index', [
                'topic_id' => $topic->id,
                'difficulty' => 2,
                'page' => 1,
            ]));

        $response->assertOk();
        $lessonResponse = $response->json('lessons');
        $this->assertCount(10, $lessonResponse);
        // expect descending created_at order by default
        $this->assertEquals(
            $lessons
                ->sortByDesc('created_at')
                ->take(10)
                ->pluck('id')
                ->toArray(),
            array_column($lessonResponse, 'id')
        );

        // get page 2
        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.index', [
                'topic_id' => $topic->id,
                'difficulty_level' => 2,
                'page' => 2,
            ]));
        
        $response->assertOk();
        $lessonResponse = $response->json('lessons');
        $this->assertCount(9, $lessonResponse);
        // expect descending created_at order by default
        $this->assertEquals(
            $lessons
                ->sortByDesc('created_at')
                ->skip(10)
                ->pluck('id')
                ->toArray(),
            array_column($lessonResponse, 'id')
        );
    }

    public function test_fetches_lessons_with_created_at_sorting_direction()
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();

        $lessons = Lesson::factory()->count(19)->create([
            'topic_id' => $topic->id,
            'difficulty_level' => 2,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.index', [
                'topic_id' => $topic->id,
                'difficulty_level' => 2,
                'page' => 2,
                'created_at_order' => 'asc',
            ]));

        $response->assertOk();
        $lessonResponse = $response->json('lessons');
        $this->assertCount(9, $lessonResponse);
        // expect descending created_at order by default
        $this->assertEquals(
            $lessons
                ->sortBy('created_at')
                ->skip(10)
                ->take(10)
                ->pluck('id')
                ->toArray(),
            array_column($lessonResponse, 'id')
        );
    }

    public function test_fetches_lessons_with_difficulty_level_sorting()
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            Lesson::factory()->create([
                'topic_id' => $topic->id,
                'difficulty_level' => $i + 1,
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.index', [
                'topic_id' => $topic->id,
                'difficulty_level' => 2,
                'page' => 2,
                'growing_difficulty' => true
            ]));

        $response->assertOk();
        $lessonResponse = $response->json('lessons');
        $this->assertCount(9, $lessonResponse);
        // expect descending created_at order by default
        $this->assertEquals(
            Lesson::query()
                ->skip(10)
                ->take(10)
                ->where('difficulty_level', '>=', 2)
                ->orderBy('difficulty_level', 'asc')
                ->pluck('id')
                ->toArray(),
            array_column($lessonResponse, 'id')
        );
    }

    public function test_fetches_lessons_matching_search_query()
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $lessons = Lesson::factory()->count(20)->create([
            'topic_id' => $topic->id,
            'difficulty_level' => 2,
        ]);
        $expectedLesson = Lesson::factory()->create([
            'topic_id' => $topic->id,
            'difficulty_level' => 2,
            'title' => 'Foo Bar Query',
        ]);

        // exclude this lesson from the search results
        // by setting difficulty level to 1
        Lesson::factory()->create([
            'topic_id' => $topic->id,
            'difficulty_level' => 1,
            'title' => 'Foo Bar Query',
        ]);

        // exclude another lesson from the search results
        // by setting topic_id to a different topic
        Lesson::factory()->create([
            'difficulty_level' => 2,
            'title' => 'Foo Bar Query',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.index', [
                'topic_id' => $topic->id,
                'difficulty_level' => 2,
                'query' => 'Foo Bar',
            ]));

        $response->assertOk();
        $lessonResponse = $response->json('lessons');
        $this->assertCount(1, $lessonResponse);
        $this->assertEquals($expectedLesson->id, $lessonResponse[0]['id']);
    }

    public function test_single_lesson_includes_body()
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();
        $this->actingAs($user)
            ->getJson(route('api.lesson.show', $lesson->id))
            ->assertOk()
            ->assertJson([
                'lesson' => [
                    'body' => $lesson->body,
                ]
            ]);
        
        $lessons = $this->actingAs($user)
            ->getJson(route('api.lesson.index', [
                'topic_id' => $lesson->topic_id,
                'difficulty_level' => $lesson->difficulty_level,
            ]))
            ->json('lessons');
        
        $this->assertCount(1, $lessons);
        $this->assertArrayNotHasKey('body', $lessons[0]);
    }

    public function test_single_lesson_includes_expected_prev_and_next_links()
    {
        $topic = Topic::factory()->create();

        $prevLesson = Lesson::factory()->create([
            'difficulty_level' => 1,
            'topic_id' => $topic->id,
        ]);

        $currentLesson = Lesson::factory()->create([
            'difficulty_level' => 2,
            'topic_id' => $topic->id,
        ]);

        $nextLesson = Lesson::factory()->create([
            'difficulty_level' => 3,
            'topic_id' => $topic->id,
        ]);

        $finalLesson = Lesson::factory()->create([
            'difficulty_level' => 4,
            'topic_id' => $topic->id,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.show',[
                'lesson_id' => $currentLesson->id,
                'growing_difficulty' => false,
                'topic_id' => $topic->id,
                'difficulty_level' => 2,
            ]));
        
        $response->assertOk();
        $lessonResponse = $response->json('lesson');
        $nextLessonUrl = $response->json('next_lesson_url');
        $prevLessonUrl = $response->json('prev_lesson_url');

        $this->assertEquals($currentLesson->id, $lessonResponse['id']);
        $this->assertNull($prevLessonUrl);
        $this->assertNull($nextLessonUrl);

        $urlParams = [
            'topic_id' => $topic->id,
            'difficulty_level' => 2,
            'lesson_id' => $currentLesson->id,
            'growing_difficulty' => true,
        ];

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.show', $urlParams));

        $response->assertOk();
        $lessonResponse = $response->json('lesson');
        $nextLessonUrl = $response->json('next_lesson_url');
        $prevLessonUrl = $response->json('prev_lesson_url');

        $this->assertEquals($currentLesson->id, $lessonResponse['id']);
        $this->assertEquals(route('api.lesson.show', array_merge($urlParams, [
            'lesson_id' => $nextLesson->id,
        ])), $nextLessonUrl);

        $this->assertEquals(route('api.lesson.show', array_merge($urlParams, [
            'lesson_id' => $prevLesson->id,
        ])), $prevLessonUrl);

        $urlParams = [
            ...$urlParams,
            'difficulty_level_direction' => 'desc'
        ];

        $response = $this->actingAs($user)
            ->getJson(route('api.lesson.show', $urlParams));
        
        $response->assertOk();
        $lessonResponse = $response->json('lesson');
        $nextLessonUrl = $response->json('next_lesson_url');
        $prevLessonUrl = $response->json('prev_lesson_url');

        $this->assertEquals($currentLesson->id, $lessonResponse['id']);
        $this->assertEquals(route('api.lesson.show', array_merge($urlParams, [
            'lesson_id' => $prevLesson->id,
        ])), $nextLessonUrl);

        $this->assertEquals(route('api.lesson.show', array_merge($urlParams, [
            'lesson_id' => $nextLesson->id,
        ])), $prevLessonUrl);
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