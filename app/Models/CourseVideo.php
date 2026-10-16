<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** One class of a course (department): a video (uploaded file or link) plus optional note files. */
class CourseVideo extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = ['release_on' => 'date', 'paid_only' => 'boolean'];

    public const TYPES = ['upload' => 'ভিডিও আপলোড', 'url' => 'ভিডিও লিংক'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(CourseFile::class)->orderBy('id');
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'video_views')->withTimestamps();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('class_no is null')->orderBy('class_no')->orderBy('id');
    }

    /** Classes of the student's course meant for their batch (locked ones included — see lockReason). */
    public function scopeForStudent(Builder $query, Student $student): Builder
    {
        return $query->where('department_id', $student->batch->department_id)
            ->where(fn ($q) => $q->whereNull('batch_id')->orWhere('batch_id', $student->batch_id));
    }

    /** Whether the student may see this class at all (right course and batch, not dropped). */
    public function isFor(Student $student): bool
    {
        return $student->status !== 'dropped'
            && $this->department_id === $student->batch->department_id
            && ($this->batch_id === null || $this->batch_id === $student->batch_id);
    }

    /** Why the class is still locked for this student, or null when it is open. */
    public function lockReason(Student $student): ?string
    {
        if ($this->release_on && $this->release_on->isFuture()) {
            return bn_date($this->release_on).' থেকে দেখা যাবে';
        }
        if ($this->paid_only && $student->totalDue() > 0) {
            return 'বকেয়া ফি পরিশোধ করলে দেখা যাবে';
        }

        return null;
    }

    public function canOpen(Student $student): bool
    {
        return $this->isFor($student) && $this->lockReason($student) === null;
    }

    public function isUpload(): bool
    {
        return $this->type === 'upload';
    }

    /** Link opened in the Lity lightbox for "url" videos. */
    public function playUrl(): string
    {
        if ($id = GalleryPhoto::youtubeId($this->url)) {
            return 'https://www.youtube.com/watch?v='.$id; // the only YouTube form Lity understands
        }
        // Google Drive share links only play inside a frame as /preview.
        if (preg_match('~drive\.google\.com/(?:file/d/|open\?id=)([\w-]+)~', (string) $this->url, $m)) {
            return "https://drive.google.com/file/d/{$m[1]}/preview";
        }

        return (string) $this->url;
    }

    public function thumb(): ?string
    {
        $id = $this->isUpload() ? null : GalleryPhoto::youtubeId($this->url);

        return $id ? "https://i.ytimg.com/vi/{$id}/mqdefault.jpg" : null;
    }

    /** Who can see it, for the admin list. */
    public function rulesLabel(): string
    {
        return collect([
            $this->batch ? 'শুধু '.$this->batch->code : null,
            $this->release_on ? bn_date($this->release_on).' থেকে' : null,
            $this->paid_only ? 'ফি পরিশোধিতদের জন্য' : null,
        ])->filter()->implode(' · ');
    }

    /**
     * Send the uploaded file. On the live server PHP only checks access and Caddy streams the file
     * (X-Accel-Redirect), so a long video never ties up one of the few PHP workers.
     */
    public function fileResponse()
    {
        if (config('app.video_sendfile')) {
            return response('', 200, ['X-Accel-Redirect' => '/'.$this->path]);
        }

        return response()->file(Storage::disk('local')->path($this->path));
    }

    /** Remove the stored video and note files (before deleting the row). */
    public function deleteStoredFiles(): void
    {
        Storage::disk('local')->delete(array_filter([$this->path, ...$this->files->pluck('path')]));
    }

    public function activityLabel(): string
    {
        return ($this->class_no ? 'ক্লাস '.$this->class_no.' · ' : '').$this->title;
    }
}
