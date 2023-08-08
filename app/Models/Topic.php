<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }
}
