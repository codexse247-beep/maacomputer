<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    protected $guarded = [];

    protected $casts = ['answers' => 'array', 'submitted_at' => 'datetime'];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Past the time limit (counted from when the student opened the quiz)? A minute of grace for slow connections. */
    public function timeUp(): bool
    {
        return $this->quiz->time_limit && $this->created_at->copy()->addMinutes($this->quiz->time_limit + 1)->isPast();
    }

    public function secondsLeft(): ?int
    {
        return $this->quiz->time_limit ? max(0, (int) now()->diffInSeconds($this->created_at->copy()->addMinutes($this->quiz->time_limit), false)) : null;
    }
}
