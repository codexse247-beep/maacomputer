<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    protected $guarded = [];

    public const TYPES = [
        'admission' => ['ভর্তির আবেদন', 'b-g'],
        'contact' => ['মেসেজ', 'b-b'],
        'password' => ['পাসওয়ার্ড রিসেট', 'b-a'],
    ];

    protected function casts(): array
    {
        return ['dob' => 'date'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeTone(): string
    {
        return self::TYPES[$this->type][1] ?? 'b-n';
    }
}
