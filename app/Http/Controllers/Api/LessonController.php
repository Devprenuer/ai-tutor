<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LessonResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function show(Request $req, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $lessonQuery = Lesson::search($req->query());

        $nextLesson = $lessonQuery
            ->next($lesson, $req->query())
            ->first();

        $prevLesson = $lessonQuery
            ->prev($lesson, $req->query())
            ->first();

        return response()->json([
            'lesson' => $lesson,
            'next_lesson' => $nextLesson,
            'prev_lesson' => $prevLesson
        ]);
    }

    public function index(Request $req)
    {
        $req->validate([
            'topic_id' => 'integer|exists:topics,id',
            'question_ids' => 'array',
            'difficulty_level' => 'integer|between:1,10',
            'difficulty_level_direction' => 'in:asc,desc',
            'created_at_direction' => 'in:asc,desc',
            'query' => 'string|max:50'
        ]);

        $lessons = Lesson::search($req->query())
            ->paginate(10);

        return response()->json([
            'lessons' => LessonResource::collection($lessons)
        ]);
    }
}
