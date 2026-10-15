<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'passed' => ['উত্তীর্ণ', 'b-g'],
        'failed' => ['অনুত্তীর্ণ', 'b-r'],
        'pending' => ['অপেক্ষমাণ', 'b-n'],
    ];

    protected function casts(): array
    {
        return ['held_on' => 'date', 'published' => 'boolean'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scoreText(): string
    {
        return $this->score === null ? '—' : bn($this->score).' / '.bn($this->max_score);
    }

    public function percent(): ?float
    {
        return $this->score === null || ! $this->max_score ? null : $this->score / $this->max_score * 100;
    }

    public function grade(): array
    {
        return grade_for($this->percent());
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'b-n';
    }
}
