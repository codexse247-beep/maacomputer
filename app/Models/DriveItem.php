<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * A file or folder in Admin › ড্রাইভ. Soft-deleted = in the trash; trashing a folder trashes everything inside it
 * with the same deleted_at, so restoring brings the whole folder back.
 */
class DriveItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['is_folder' => 'bool', 'starred' => 'bool', 'size' => 'int'];

    /** Folder on the private "local" disk where uploads are kept, and where unfinished uploads wait. */
    public const DIR = 'drive';

    public const TMP = 'drive-tmp';

    /** Types the browser may show directly ("open"); everything else is downloaded. */
    public const INLINE = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'video/mp4', 'video/webm', 'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/mp4', 'text/plain'];

    /** Icon kind per extension: [kind => [extensions]]. Colours are in the drive page. */
    private const KINDS = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'tif', 'tiff'],
        'video' => ['mp4', 'webm', 'mkv', 'avi', 'mov', 'wmv', '3gp'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'],
        'pdf' => ['pdf'],
        'doc' => ['doc', 'docx', 'odt', 'rtf', 'txt'],
        'sheet' => ['xls', 'xlsx', 'csv', 'ods'],
        'slide' => ['ppt', 'pptx', 'odp'],
        'zip' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'design' => ['psd', 'ai', 'cdr', 'eps', 'indd', 'fig'],
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Folders from the top down to (not including) this item. */
    public function ancestors(): array
    {
        $chain = [];
        $item = $this->parent_id ? self::withTrashed()->find($this->parent_id) : null;
        while ($item && count($chain) < 50) {
            array_unshift($chain, $item);
            $item = $item->parent_id ? self::withTrashed()->find($item->parent_id) : null;
        }

        return $chain;
    }

    /** Ids of everything inside these folders, at any depth (trashed or not). */
    public static function descendantIds(array $ids): array
    {
        $all = [];
        $level = $ids;
        while ($level) {
            $level = self::withTrashed()->whereIn('parent_id', $level)->pluck('id')->all();
            $all = array_merge($all, $level);
        }

        return $all;
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    public function kind(): string
    {
        if ($this->is_folder) {
            return 'folder';
        }
        foreach (self::KINDS as $kind => $extensions) {
            if (in_array($this->extension(), $extensions, true)) {
                return $kind;
            }
        }

        return 'file';
    }

    public function canOpen(): bool
    {
        return ! $this->is_folder && in_array($this->mime, self::INLINE, true);
    }

    /** Small enough to use the picture itself as the icon. */
    public function hasThumb(): bool
    {
        return $this->canOpen() && str_starts_with((string) $this->mime, 'image/') && $this->size <= 3 * 1048576;
    }

    public static function sizeText(int $bytes): string
    {
        return match (true) {
            $bytes >= 1073741824 => bn(round($bytes / 1073741824, 2)).' GB',
            $bytes >= 1048576 => bn(round($bytes / 1048576, 1)).' MB',
            default => bn(max($bytes ? 1 : 0, (int) round($bytes / 1024))).' KB',
        };
    }

    /** The file itself; Caddy streams it when VIDEO_SENDFILE is on so no PHP worker stays busy. */
    public function fileResponse(bool $inline = false)
    {
        // Old browsers get a plain-English version of the name; all current ones use the real (Bangla) name.
        $fallback = preg_replace('/[^A-Za-z0-9 ._()-]/', '_', Str::ascii($this->name)) ?: 'file';
        $disposition = HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $this->name, $fallback);

        if (config('app.video_sendfile')) {
            return response('', 200, ['X-Accel-Redirect' => '/'.$this->path, 'Content-Disposition' => $disposition]);
        }

        return response()->file(Storage::disk('local')->path($this->path), [
            'Content-Type' => $inline ? $this->mime : 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ]);
    }

    public function activityLabel(): string
    {
        return ($this->is_folder ? 'ফোল্ডার: ' : '').$this->name;
    }
}
