<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'enrolling' => ['ভর্তি চলছে', 'b-b'],
        'running' => ['চলমান', 'b-g'],
        'completed' => ['সমাপ্ত', 'b-n'],
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function teacherRecord(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function activeStudents(): HasMany
    {
        return $this->students()->where('status', 'active');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'b-n';
    }

    public function label(): string
    {
        return $this->code.' · '.$this->name;
    }

    /** Digits of the code, used in student IDs: GD-2607 → 2607. */
    public function serial(): string
    {
        return preg_replace('/\D/', '', $this->code) ?: (string) $this->id;
    }
}
