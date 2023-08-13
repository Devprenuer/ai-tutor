<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\HasViewCount;

class Question extends Model
{
    use HasFactory, HasViewCount;

    const QUESTION_TYPES = [
        'CODING' => ['ID' => 0, 'TITLE' => 'Coding'], // code is the default
        'MULTIPLE_CHOICE' => ['ID' => 1, 'TITLE' => 'Multiple Choice'],
    ];

    protected $fillable = [
        'question',
        'difficulty_level',
        'topic_id',
        'question_type'
    ];

    protected $hidden = [
        'multiple_choice_answer'
    ];

    protected $casts = [
        'multiple_choice_options' => 'json',
    ];

    public static function questionTypeById(int $id): array
    {
        foreach (self::QUESTION_TYPES as $questionType) {
            if ($questionType['ID'] === $id) {
                return $questionType;
            }
        }

        throw new \Exception('Invalid question type id');
    }

    public static function getViewModelClassName(): string
    {
        return UserQuestionView::class;
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
