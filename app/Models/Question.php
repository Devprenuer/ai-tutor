<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'question',
        'difficulty_level',
        'topic_id',
    ];

    use HasFactory;

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function userQuestionViews()
    {
        return $this->hasMany(UserQuestionView::class);
    }

    public function addViewByUser($userId)
    {
        return $this->userQuestionViews()->create([
            'user_id' => $userId
        ]);
    }

    public static function whereNotViewedByUser($userId)
    {
        return static::whereNotExists(function($query) use ($userId) {
            $query->selectRaw(1)
                ->from('user_question_views')
                ->whereColumn('questions.id', 'question_id')
                ->where('user_id', $userId);
        });
    }

    public static function whereViewedByUser($userId)
    {
        return static::whereExists(function($query) use ($userId) {
            $query->selectRaw(1)
                ->from('user_question_views')
                ->whereColumn('questions.id', 'question_id')
                ->where('user_id', $userId);
        });
    }
}
