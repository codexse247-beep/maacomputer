<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    use LogsActivity;

    protected $guarded = [];

    public const TAGS = [
        'admission' => ['ভর্তি', 'b-g'],
        'result' => ['ফলাফল', 'b-b'],
        'exam' => ['পরীক্ষা', 'b-a'],
        'holiday' => ['ছুটি', 'b-n'],
        'fee' => ['ফি', 'b-a'],
        'certificate' => ['সনদ', 'b-g'],
        'general' => ['সাধারণ', 'b-n'],
    ];

    protected function casts(): array
    {
        return ['published_on' => 'date', 'is_public' => 'boolean'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function audienceLabel(): string
    {
        $who = $this->batch ? $this->batch->code : ($this->department ? $this->department->name : 'সবাই');

        return $who.($this->is_public ? ' · ওয়েবসাইটে দৃশ্যমান' : ' · শুধু পোর্টাল');
    }

    public function scopeForStudent(Builder $query, Student $student): Builder
    {
        $departmentId = $student->batch?->department_id;

        return $query->where(fn ($q) => $q->where(fn ($a) => $a->whereNull('batch_id')->whereNull('department_id'))
            ->orWhere('batch_id', $student->batch_id)
            ->orWhere(fn ($d) => $d->whereNull('batch_id')->where('department_id', $departmentId)));
    }

    public function tagLabel(): string
    {
        return self::TAGS[$this->tag][0] ?? $this->tag;
    }

    public function tagTone(): string
    {
        return self::TAGS[$this->tag][1] ?? 'b-n';
    }

    public function activityLabel(): string
    {
        return $this->title;
    }
}
