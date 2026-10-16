<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use ForCourse, LogsActivity;

    protected $guarded = [];

    protected $casts = ['due_on' => 'date'];

    /** Files students may hand in (and teachers may attach). */
    public const EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,psd,ai,cdr,jpg,jpeg,png,html,css,js';

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function isClosed(): bool
    {
        return $this->due_on !== null && $this->due_on->endOfDay()->isPast();
    }

    public function activityLabel(): string
    {
        return $this->title;
    }
}
