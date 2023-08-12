<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\HasViewCount;
use App\Models\Question;

class Hint extends Model
{
    use HasFactory, HasViewCount;

    protected $fillable = [
        'hint',
        'question_id',
        'helpfulness_level'
    ];

    public static function getViewModelClassName(): string
    {
        return UserHintView::class;
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
