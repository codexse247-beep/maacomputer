<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Department extends Model
{
    protected $guarded = [];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(Student::class, Batch::class);
    }

    /** "What you will learn" lines for the public departments page. */
    public function learnList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/?
/', (string) $this->learn))));
    }

    /** Short topic chips shown on the public site, e.g. "ফটোশপ, ইলাস্ট্রেটর". */
    public function topicList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->topics))));
    }
}
