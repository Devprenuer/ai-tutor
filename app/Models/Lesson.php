<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasViewCount;
use Illuminate\Database\Eloquent\Model;
use App\Models\Topic;
use App\Models\Question;
use App\Models\UserLessonView;
use InvalidArgumentException;

class Lesson extends Model
{
    use HasFactory, HasViewCount;

    protected $fillable = [
        'title',
        'body',
        'excerpt',
        'topic_id'
    ];

    protected $appends = [
        'link'
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'lesson_questions');
    }

    public static function getViewModelClassName(): string
    {
        return UserLessonView::class;
    }

    public function scopeNextRecord($query, Lesson $lesson, array $params)
    {
        $currentCreatedAt = $lession->created_at;
        $currentDifficulty = $lesson->difficulty_level;
        $createdAtDirection = $params['created_at_direction'] ?? 'desc';
        $difficultyLevelDirection = $params['difficulty_level_direction'] ?? 'asc';
        $direction = $params['direction'] ?? 'next';

        if ($direction === 'next') {
            $createdAtOperator = $createdAtDirection === 'desc' ? '<' : '>';
            $difficultyLevelOperator = $difficultyLevelDirection === 'desc' ? '<' : '>';
        } elseif ($direction === 'prev') {
            $createdAtOperator = $createdAtDirection === 'desc' ? '>' : '<';
            $difficultyLevelOperator = $difficultyLevelDirection === 'desc' ? '>' : '<';
        } else {
            throw new InvalidArgumentException('Invalid direction, must be "next" or "prev"');
        }

        return $query
            ->where(function ($query) use (
                $currentCreatedAt,
                $currentDifficulty,
                $createdAtOperator,
                $difficultyLevelOperator,
            ) {
                $query->where('difficulty_level', '=', $currentDifficulty)
                    ->where('created_at', $createdAtOperator, $currentCreatedAt)
                    ->orWhere('difficulty_level', $difficultyLevelOperator, $currentDifficulty);
            })
    }

    public function scopeNext($query, Lesson $lesson, array $params)
    {
        return $query
            ->nextRecord($lesson, [
                ...$params,
                'direction' => 'next'
            ]);
    }

    public function scopePrev($query, $currentCreatedAt, $currentDifficulty)
    {
        return $query
            ->nextRecord($lesson, [
                ...$params,
                'direction' => 'prev'
            ]);
    }

    public function scopeSearch($builder, array $params) {
        $questionIds = $params['question_ids'] ?? [];
        $topicId = $params['topic_id'] ?? null;

        if (!empty($questionIds)) {
            $questionQuery = Question::whereIn('id', $questionIds);

            if ($topicId) {
                $questionQuery = $questionQuery
                    ->where('topic_id', $topicId);
            }

            if ($questionQuery->count() !== count($questionIds)) {
                // cant get questions from mixed topic ids
                // or with invalid question ids
                throw new InvalidArgumentException('Invalid question ids');
            }
        }

        $lessonQuery = $builder;

        if ($topicId) {
            $lessonQuery = $lessonQuery
                ->where('topic_id', $topicId);
        }

        if (!empty($questionIds)) {
            $lessonQuery = $lessonQuery
                ->whereHas('questions', function ($query) use ($questionIds) {
                    $query->whereIn('id', $questionIds);
                });
        }

        $difficultyLevel = $params['difficulty_level'] ?? null;
        if ($difficultyLevel) {
            $lessonQuery = $lessonQuery
                ->where('difficulty_level', $difficultyLevel);
        }

        $query = $params['query'] ?? null;
        if ($query) {
            $lessonQuery = $lessonQuery
                ->where('title', 'like', '%' . $query . '%');
        }

        $difficultyLevelDirection = $params['difficulty_level_direction'] ?? 'asc';
        $createdAtDirection = $params['created_at_direction'] ?? 'desc';

        return $lessonQuery
            ->select('id', 'title', 'excerpt', 'difficulty_level', 'created_at', 'view_count')
            ->orderBy('created_at', $createdAtDirection)
            ->orderBy('difficulty_level', $difficultyLevelDirection);
    }
}
