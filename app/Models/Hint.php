<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Question;

class Hint extends Model
{
    use HasFactory;

    protected $fillable = [
        'hint',
        'question_id',
        'helpfulness_level'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
