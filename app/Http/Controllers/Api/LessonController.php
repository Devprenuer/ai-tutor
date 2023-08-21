<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LessonResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;

class LessonController extends Controller
{
    private function validateRequest(Request $req)
    {
        $req->validate([
            'topic_id' => 'integer|exists:topics,id',
            'question_ids' => 'array',
            'question_type' => 'integer|between:0,1',
            'difficulty_level' => 'integer|between:1,10',
            'difficulty_level_direction' => 'in:asc,desc',
            'growing_difficulty' => 'integer|between:0,1',
            'created_at_direction' => 'in:asc,desc',
            'query' => 'string|max:50'
        ]);
    }

    public function show(Request $req, $id)
    {
        $this->validateRequest($req);

        $lesson = Lesson::findOrFail($id);
        $user = $req->user();

        if (!$lesson->viewedByUser($user->id)) {
            $lesson->addViewByUser($user->id);
        }

        $params = $req->query();

        $nextLesson = Lesson::query()
            ->select('id')
            ->where('topic_id', $lesson->topic_id)
            ->next($lesson, $params)
            ->first();

        $prevLesson = Lesson::query()
            ->select('id')
            ->where('topic_id', $lesson->topic_id)
            ->prev($lesson, $params)
            ->first();
        
        $queryString = http_build_query($req->query());
        $nextLessonUrl =
            $nextLesson ? route('api.lesson.show', $nextLesson->id) .'?' .$queryString : null;
        $prevLessonUrl =
            $prevLesson ? route('api.lesson.show', $prevLesson->id) .'?' .$queryString : null;

        return response()->json([
            'lesson' => $lesson,
            'next_lesson_url' => $nextLessonUrl,
            'prev_lesson_url' => $prevLessonUrl
        ]);
    }

    public function index(Request $req)
    {
        $this->validateRequest($req);

        $lessons = Lesson::search($req->query())
            ->paginate(10);

        return response()->json([
            'lessons' => LessonResource::collection($lessons)
        ]);
    }
}
