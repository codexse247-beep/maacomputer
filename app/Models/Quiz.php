<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use ForCourse, LogsActivity;

    protected $guarded = [];

    protected $casts = ['closes_on' => 'date', 'show_answers' => 'boolean', 'published' => 'boolean'];

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isClosed(): bool
    {
        return $this->closes_on !== null && $this->closes_on->endOfDay()->isPast();
    }

    public function activityLabel(): string
    {
        return $this->title;
    }
}
