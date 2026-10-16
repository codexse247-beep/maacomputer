<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $guarded = [];

    public const STATUSES = ['sent' => ['পাঠানো হয়েছে', 'b-g'], 'failed' => ['ব্যর্থ', 'b-r'], 'skipped' => ['বন্ধ ছিল', 'b-n']];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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
