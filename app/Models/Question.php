<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\HasViewCount;

class Question extends Model
{
    use HasFactory, HasViewCount;

    protected $fillable = [
        'question',
        'difficulty_level',
        'topic_id',
    ];

    public static function getViewModelClassName(): string
    {
        return UserQuestionView::class;
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
