<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A note / practice file attached to a class (stored on the private "local" disk). */
class CourseFile extends Model
{
    protected $guarded = [];

    /** Allowed upload extensions (notes, office files, design files, archives, pictures). */
    public const EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,psd,ai,cdr,jpg,jpeg,png';

    public function video(): BelongsTo
    {
        return $this->belongsTo(CourseVideo::class, 'course_video_id');
    }

    /** Download under the original file name (access is checked by the caller). */
    public function downloadResponse()
    {
        return Storage::disk('local')->download($this->path, $this->name);
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576 ? bn(round($this->size / 1048576, 1)).' MB' : bn(max(1, (int) round($this->size / 1024))).' KB';
    }
}
