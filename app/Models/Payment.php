<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use LogsActivity;

    protected $guarded = [];

    public const METHODS = ['নগদ (ক্যাশ)', 'বিকাশ', 'নগদ (মোবাইল)', 'রকেট', 'ব্যাংক'];

    protected function casts(): array
    {
        return ['paid_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public static function nextReceiptNo(): string
    {
        $last = static::query()->orderByDesc('id')->value('receipt_no');
        $n = $last ? (int) preg_replace('/\D/', '', $last) + 1 : 1;

        return 'MR-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    public function activityLabel(): string
    {
        return $this->receipt_no.' · ৳'.$this->amount.' · '.($this->student?->name_bn ?? '');
    }
}
