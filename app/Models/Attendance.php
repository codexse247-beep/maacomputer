<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $guarded = [];

    public const STATUSES = ['present' => 'উপস্থিত', 'absent' => 'অনুপস্থিত', 'leave' => 'ছুটি'];

    protected function casts(): array
    {
        return ['date' => 'date', 'present' => 'boolean'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
