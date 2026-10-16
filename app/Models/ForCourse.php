<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Records meant for one course (department_id) and optionally only one of its batches (batch_id). */
trait ForCourse
{
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function scopeForStudent(Builder $query, Student $student): Builder
    {
        return $query->where('department_id', $student->batch->department_id)
            ->where(fn ($q) => $q->whereNull('batch_id')->orWhere('batch_id', $student->batch_id));
    }

    public function isFor(Student $student): bool
    {
        return $student->status !== 'dropped'
            && $this->department_id === $student->batch->department_id
            && ($this->batch_id === null || $this->batch_id === $student->batch_id);
    }

    public function audienceLabel(): string
    {
        return ($this->department?->name ?? '').($this->batch ? ' · শুধু '.$this->batch->code : ' · সব ব্যাচ');
    }
}
