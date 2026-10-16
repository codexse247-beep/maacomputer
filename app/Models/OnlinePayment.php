<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A fee the student reports paying by mobile banking; becomes a Payment once the office approves it. */
class OnlinePayment extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = ['reviewed_at' => 'datetime'];

    /** method => [label, Payment::METHODS value, settings key holding the receiving number] */
    public const METHODS = [
        'bkash' => ['বিকাশ', 'বিকাশ', 'bkash_number'],
        'nagad' => ['নগদ', 'নগদ (মোবাইল)', 'nagad_number'],
        'rocket' => ['রকেট', 'রকেট', 'rocket_number'],
    ];

    public const STATUSES = ['pending' => ['যাচাই বাকি', 'b-a'], 'approved' => ['গৃহীত', 'b-g'], 'rejected' => ['বাতিল', 'b-r']];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Methods the centre has a number for (set in Settings). */
    public static function available(): array
    {
        return array_filter(self::METHODS, fn ($m) => filled(Setting::values()[$m[2]] ?? null));
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method][0] ?? $this->method;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'b-n';
    }

    public function activityLabel(): string
    {
        return $this->methodLabel().' ৳'.$this->amount.' · TrxID '.$this->trx_id.' · '.($this->student?->name_bn ?? '');
    }
}
