<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = ['spent_on' => 'date'];

    public const CATEGORIES = ['বাড়ি ভাড়া', 'শিক্ষক / স্টাফ বেতন', 'বিদ্যুৎ বিল', 'ইন্টারনেট', 'কম্পিউটার ও যন্ত্রপাতি', 'মেরামত', 'প্রচার / মার্কেটিং', 'অফিস খরচ', 'অন্যান্য'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityLabel(): string
    {
        return $this->category.' · ৳'.$this->amount.($this->note ? ' · '.$this->note : '');
    }
}
