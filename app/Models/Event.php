<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A calendar entry: holiday, exam or event — for everyone, one course or one batch. */
class Event extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'is_public' => 'boolean'];

    public const TYPES = [
        'holiday' => ['ছুটি', 'b-r', '#B00E28'],
        'exam' => ['পরীক্ষা', 'b-a', '#8A5300'],
        'event' => ['অনুষ্ঠান', 'b-b', '#1F5FA8'],
        'class' => ['বিশেষ ক্লাস', 'b-g', '#176008'],
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** Entries for the whole centre, the student's course, or their batch. */
    public function scopeForStudent(Builder $query, Student $student): Builder
    {
        return $query->where(fn ($q) => $q->where(fn ($a) => $a->whereNull('department_id')->whereNull('batch_id'))
            ->orWhere('batch_id', $student->batch_id)
            ->orWhere(fn ($d) => $d->whereNull('batch_id')->where('department_id', $student->batch->department_id)));
    }

    /** Entries touching the given date range. */
    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->where('starts_on', '<=', $to)->where(fn ($q) => $q->where('starts_on', '>=', $from)->orWhere('ends_on', '>=', $from));
    }

    public function lastDay()
    {
        return $this->ends_on ?? $this->starts_on;
    }

    public function covers(\Carbon\CarbonInterface $day): bool
    {
        return $day->betweenIncluded($this->starts_on->copy()->startOfDay(), $this->lastDay()->copy()->endOfDay());
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeTone(): string
    {
        return self::TYPES[$this->type][1] ?? 'b-n';
    }

    public function color(): string
    {
        return self::TYPES[$this->type][2] ?? '#55635A';
    }

    public function audienceLabel(): string
    {
        return $this->batch ? $this->batch->code : ($this->department ? $this->department->name : 'সবার জন্য');
    }

    public function dateLabel(): string
    {
        return $this->ends_on && ! $this->ends_on->isSameDay($this->starts_on)
            ? bn_date($this->starts_on, false).' – '.bn_date($this->ends_on)
            : bn_date($this->starts_on);
    }

    public function activityLabel(): string
    {
        return $this->title.' ('.$this->starts_on?->format('Y-m-d').')';
    }
}
