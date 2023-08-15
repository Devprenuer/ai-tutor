<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $hidden = [
        'created_at',
        'updated_at',
        'question_id',
        'user_id'
    ];

    protected $fillable = [
        'answer',
        'question_id',
        'user_id',
        'time_taken',
        'score',
    ];
}
