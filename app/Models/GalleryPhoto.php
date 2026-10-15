<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryPhoto extends Model
{
    protected $guarded = [];

    public const CATEGORIES = ['lab' => 'ল্যাব', 'event' => 'অনুষ্ঠান', 'work' => 'স্টুডেন্টের কাজ'];

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }
}
