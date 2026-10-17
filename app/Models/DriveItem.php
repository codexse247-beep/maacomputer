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

    /** Small previews of photos (made when first shown; can be deleted any time). */
    public const THUMBS = 'drive-thumbs';

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

    /** "image" or "video" when the file opens in the lightbox on the drive page, else null. */
    public function viewer(): ?string
    {
        return match (true) {
            ! $this->canOpen() => null,
            str_starts_with($this->mime, 'image/') => 'image',
            str_starts_with($this->mime, 'video/') => 'video',
            default => null,
        };
    }

    /** A photo we can make a small preview of (up to 40 MB). */
    public function hasThumb(): bool
    {
        return ! $this->is_folder && in_array($this->mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true) && $this->size <= 40 * 1048576;
    }

    public function thumbPath(): string
    {
        return self::THUMBS.'/'.$this->id.'.jpg';
    }

    /** The EXIF "Orientation" of a phone photo (1 = upright), read directly because PHP's exif extension is off here. */
    public static function jpegOrientation(string $file): int
    {
        $data = (string) @file_get_contents($file, false, null, 0, 131072);
        $pos = 2;
        while ($pos + 4 < strlen($data) && $data[$pos] === "\xFF") {
            $marker = ord($data[$pos + 1]);
            $len = unpack('n', substr($data, $pos + 2, 2))[1];
            if ($marker === 0xE1 && substr($data, $pos + 4, 6) === "Exif\0\0") {
                $tiff = substr($data, $pos + 10, $len - 8);
                $le = substr($tiff, 0, 2) === 'II';
                $u16 = fn ($o) => unpack($le ? 'v' : 'n', substr($tiff, $o, 2))[1] ?? 0;
                $u32 = fn ($o) => unpack($le ? 'V' : 'N', substr($tiff, $o, 4))[1] ?? 0;
                $ifd = $u32(4);
                for ($i = 0, $n = $u16($ifd); $i < $n && $ifd + 14 + $i * 12 <= strlen($tiff); $i++) {
                    $entry = $ifd + 2 + $i * 12;
                    if ($u16($entry) === 0x0112) {
                        return $u16($entry + 8);
                    }
                }

                return 1;
            }
            if ($marker === 0xDA) {
                break; // picture data starts; no EXIF
            }
            $pos += 2 + $len;
        }

        return 1;
    }

    /** Write a JPEG at most $max px wide/high (turned upright for phone photos). False when the picture can't be read. */
    public static function makeThumb(string $source, string $target, int $max = 480): bool
    {
        $info = @getimagesize($source);
        // Very large pictures would need more memory than PHP has.
        if (! $info || $info[0] * $info[1] > 50_000_000) {
            return false;
        }
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => false,
        };
        if (! $img) {
            return false;
        }
        if ($info[2] === IMAGETYPE_JPEG && ($angle = [3 => 180, 6 => -90, 8 => 90][self::jpegOrientation($source)] ?? 0)) {
            $img = imagerotate($img, $angle, 0);
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, $max / max($w, $h));
        $thumb = imagecreatetruecolor(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
        imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255)); // see-through PNGs on white
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, imagesx($thumb), imagesy($thumb), $w, $h);
        @mkdir(dirname($target), 0775, true);
        $ok = imagejpeg($thumb, $target, 80);
        imagedestroy($img);
        imagedestroy($thumb);

        return $ok;
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
