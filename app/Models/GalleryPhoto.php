<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One gallery item: an uploaded image or a YouTube video. */
class GalleryPhoto extends Model
{
    protected $guarded = [];

    public const CATEGORIES = ['lab' => 'ল্যাব', 'event' => 'অনুষ্ঠান', 'work' => 'স্টুডেন্টের কাজ'];

    public const TYPES = ['image' => 'ছবি', 'video' => 'ভিডিও'];

    /** Pull the 11-character video ID out of any common YouTube link (watch, youtu.be, shorts, embed, live). */
    public static function youtubeId(?string $url): ?string
    {
        return preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~i', (string) $url, $m) ? $m[1] : null;
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /** Picture shown in the grid. */
    public function thumb(): string
    {
        return $this->isVideo() ? "https://i.ytimg.com/vi/{$this->youtube_id}/hqdefault.jpg" : asset('storage/'.$this->path);
    }

    /** What opens in the lightbox. Lity only understands the standard watch link, so always build that. */
    public function url(): string
    {
        return $this->isVideo() ? 'https://www.youtube.com/watch?v='.$this->youtube_id : asset('storage/'.$this->path);
    }
}
