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
        'difficulty_level',
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
        $currentCreatedAt = $lesson->created_at;
        $currentDifficulty = $lesson->difficulty_level;
        $createdAtDirection = $params['created_at_direction'] ?? 'desc';
        $difficultyLevelDirection = $params['difficulty_level_direction'] ?? 'asc';
        $direction = $params['direction'] ?? 'next';

        if ($direction === 'next') {
            $createdAtOperator = $createdAtDirection === 'desc' ? '<' : '>';
            $createdAtOrder = $createdAtDirection === 'desc' ? 'desc' : 'asc';
            $difficultyLevelOperator = $difficultyLevelDirection === 'desc' ? '<=' : '>=';
        } elseif ($direction === 'prev') {
            $createdAtOperator = $createdAtDirection === 'desc' ? '>' : '<';
            $createdAtOrder = $createdAtDirection === 'desc' ? 'asc' : 'desc';
            $difficultyLevelOperator = $difficultyLevelDirection === 'desc' ? '>=' : '<=';
            $difficultyLevelDirection = $difficultyLevelDirection === 'desc' ? 'asc' : 'desc';
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
                $query
                    ->where('created_at', $createdAtOperator, $currentCreatedAt)
                    ->where('difficulty_level', $difficultyLevelOperator, $currentDifficulty);
            })
            ->limit(1)
            ->orderBy('difficulty_level', $difficultyLevelDirection)
            ->orderBy('created_at', $createdAtOrder);
    }

    public function scopeNext($query, Lesson $lesson, array $params)
    {
        return $query
            ->nextRecord($lesson, [
                ...$params,
                'direction' => 'next'
            ]);
    }

    public function scopePrev($query, Lesson $lesson, array $params)
    {
        return $query
            ->nextRecord($lesson, [
                ...$params,
                'direction' => 'prev'
            ]);
    }

    public function scopeSearch($builder, array $params)
    {
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
        $growDifficulty = $params['growing_difficulty'] ?? false;
        $difficultyLevelDirection = $params['difficulty_level_direction'] ?? 'asc';

        if ($difficultyLevelDirection) {
            if ($difficultyLevelDirection !== 'asc' && $difficultyLevelDirection !== 'desc') {
                throw new InvalidArgumentException('Invalid difficulty_level_direction');
            }
            $lessonQuery = $lessonQuery
                ->orderBy('difficulty_level', $difficultyLevelDirection);
        }

        if ($growDifficulty && $difficultyLevelDirection && $difficultyLevel) {
            $difficultyLevelOperator = $difficultyLevelDirection === 'asc' ? '>=' : '<=';
            $lessonQuery = $lessonQuery
                ->where('difficulty_level', $difficultyLevelOperator, $difficultyLevel);
        } else if ($difficultyLevel) {
            $lessonQuery = $lessonQuery
                ->where('difficulty_level', $difficultyLevel);
        }

        $query = $params['query'] ?? null;
        if ($query) {
            $lessonQuery = $lessonQuery
                ->where('title', 'like', '%' . $query . '%');
        }

        $createdAtDirection = $params['created_at_direction'] ?? 'desc';
        if ($createdAtDirection !== 'asc' && $createdAtDirection !== 'desc') {
            throw new InvalidArgumentException('Invalid created_at_direction');
        }

        $lessonQuery = $lessonQuery
            ->orderBy('created_at', $createdAtDirection);

        return $lessonQuery
            ->select(
                'id',
                'title',
                'excerpt',
                'difficulty_level',
                'created_at',
                'view_count'
            );
    }

    public function getLinkAttribute()
    {
        return route('api.lesson.show', $this->id);
    }
}
