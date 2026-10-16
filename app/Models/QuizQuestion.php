<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $guarded = [];

    protected $casts = ['options' => 'array'];

    public const LETTERS = ['ক', 'খ', 'গ', 'ঘ'];
}
