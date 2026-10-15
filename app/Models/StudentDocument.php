<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    protected $guarded = [];

    public const TYPES = [
        'photo' => 'পাসপোর্ট সাইজ ছবি',
        'nid' => 'জন্মনিবন্ধন / এনআইডি কপি',
        'certificate' => 'শিক্ষাগত সনদের কপি',
        'form' => 'স্বাক্ষরিত ভর্তি ফরম',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
