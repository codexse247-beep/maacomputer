<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use LogsActivity;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    public const STATUSES = [
        'active' => ['সক্রিয়', 'b-g'],
        'on_leave' => ['ছুটিতে', 'b-n'],
        'dropped' => ['বাদ পড়েছে', 'b-r'],
        'completed' => ['কোর্স সম্পন্ন', 'b-b'],
    ];

    public const FEE_STATES = [
        'paid' => ['পরিশোধিত', 'b-g'],
        'partial' => ['আংশিক', 'b-a'],
        'due' => ['বকেয়া', 'b-r'],
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'certificate_issued_on' => 'date',
            'admission_date' => 'date',
            'next_installment_date' => 'date',
            'education' => 'array',
            'password' => 'hashed',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class)->latest();
    }

    /** Class videos this student has marked (or played) as watched. */
    public function watchedVideos(): BelongsToMany
    {
        return $this->belongsToMany(CourseVideo::class, 'video_views')->withTimestamps();
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    // ---- Fees -------------------------------------------------------------

    public function totalPayable(): int
    {
        return max(0, $this->course_fee + $this->admission_fee - $this->discount);
    }

    public function totalPaid(): int
    {
        return (int) ($this->payments_sum_amount ?? $this->payments()->sum('amount'));
    }

    public function totalDue(): int
    {
        return max(0, $this->totalPayable() - $this->totalPaid());
    }

    public function feeState(): string
    {
        if ($this->totalDue() === 0) {
            return 'paid';
        }

        return $this->totalPaid() > 0 ? 'partial' : 'due';
    }

    public function feeLabel(): string
    {
        return self::FEE_STATES[$this->feeState()][0];
    }

    public function feeTone(): string
    {
        return self::FEE_STATES[$this->feeState()][1];
    }

    public function paidPercent(): int
    {
        return $this->totalPayable() ? (int) round($this->totalPaid() / $this->totalPayable() * 100) : 100;
    }

    // ---- Attendance -------------------------------------------------------

    /** Adds class_days_count (leave days excluded) and present_count for attendanceStats(). */
    public function scopeWithAttendance($query)
    {
        return $query->withCount([
            'attendances as class_days_count' => fn ($q) => $q->where('status', '!=', 'leave'),
            'attendances as present_count' => fn ($q) => $q->where('status', 'present'),
        ]);
    }

    public function attendanceStats(): array
    {
        $total = (int) ($this->class_days_count ?? $this->attendances()->where('status', '!=', 'leave')->count());
        $present = (int) ($this->present_count ?? $this->attendances()->where('status', 'present')->count());

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $total - $present,
            'percent' => $total ? (int) round($present / $total * 100) : null,
        ];
    }

    // ---- Display ----------------------------------------------------------

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'b-n';
    }

    public function presentAddress(): string
    {
        return collect([$this->present_village, $this->present_po, $this->present_upazila, $this->present_district])->filter()->implode(', ');
    }

    public function permanentAddress(): string
    {
        $parts = collect([
            $this->perm_village ? 'গ্রাম/রোড: '.$this->perm_village : null,
            $this->perm_po ? 'ডাকঘর: '.$this->perm_po : null,
            $this->perm_upazila ? 'উপজেলা: '.$this->perm_upazila : null,
            $this->perm_district ? 'জেলা: '.$this->perm_district : null,
        ])->filter();

        return $parts->isEmpty() ? '—' : $parts->implode(', ');
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    public function maskedNid(): string
    {
        if (! $this->nid) {
            return '—';
        }

        return '•••••••• '.bn(mb_substr($this->nid, -4));
    }

    public static function nextCodeFor(Batch $batch): array
    {
        $roll = (int) static::where('batch_id', $batch->id)->max('roll') + 1;
        do {
            $code = 'MCT-'.$batch->serial().'-'.str_pad((string) $roll, 3, '0', STR_PAD_LEFT);
            $taken = static::where('student_code', $code)->exists();
            if ($taken) {
                $roll++;
            }
        } while ($taken);

        return [$code, $roll];
    }

    public function activityLabel(): string
    {
        return $this->name_bn.' ('.$this->student_code.')';
    }
}
