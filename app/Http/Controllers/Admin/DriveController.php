<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DriveItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\Mime\MimeTypes;
use ZipArchive;

/** Admin › ড্রাইভ: folders, uploads (in small pieces, so big files get through Cloudflare), downloads, trash, share links. */
class DriveController extends Controller
{
    /** Upload piece size the browser uses (Cloudflare allows 100 MB per request; small pieces survive slow lines). */
    public const CHUNK = 5 * 1048576;

    /** Biggest single file, and space always left free on the disk for Windows and the database. */
    public const MAX_FILE = 5 * 1073741824;

    public const KEEP_FREE = 3 * 1073741824;

    /** Biggest folder / selection that is zipped for download. */
    public const MAX_ZIP = 4 * 1073741824;

    public function index(Request $request)
    {
        $view = in_array($request->query('view'), ['recent', 'starred', 'trash'], true) ? $request->query('view') : null;
        $q = trim((string) $request->query('q'));
        $folder = $request->integer('folder') ? DriveItem::where('is_folder', true)->findOrFail($request->integer('folder')) : null;
        [$sort, $dir] = $this->sort($request);

        $items = match (true) {
            $q !== '' => DriveItem::where('name', 'like', '%'.addcslashes($q, '%_\\').'%')->orderByDesc('is_folder')->orderBy($sort, $dir)->limit(300)->get(),
            $view === 'recent' => DriveItem::where('is_folder', false)->latest('updated_at')->limit(100)->get(),
            $view === 'starred' => DriveItem::where('starred', true)->orderByDesc('is_folder')->orderBy($sort, $dir)->get(),
            // Only what was trashed by itself (things inside a trashed folder come back with it).
            $view === 'trash' => DriveItem::onlyTrashed()->where(fn ($w) => $w->whereNull('parent_id')->orWhereHas('parent'))->latest('deleted_at')->get(),
            default => DriveItem::where('parent_id', $folder?->id)->orderByDesc('is_folder')->orderBy($sort, $dir)->get(),
        };
        $items->load('user:id,name');

        return view('admin.drive.index', [
            'items' => $items,
            'folder' => $folder,
            'crumbs' => $folder ? [...$folder->ancestors(), $folder] : [],
            'view' => $view,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'folders' => $this->folderPaths(),
            'used' => (int) DriveItem::withTrashed()->sum('size'),
            'free' => (int) @disk_free_space(Storage::disk('local')->path('')),
            'trashCount' => DriveItem::onlyTrashed()->count(),
        ]);
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'parent_id' => ['nullable', $this->folderRule()],
        ], [], ['name' => 'ফোল্ডারের নাম']);

        $folder = DriveItem::create(['is_folder' => true, 'name' => $this->cleanName($data['name']), 'parent_id' => $data['parent_id'] ?? null, 'user_id' => $request->user()->id]);
        ActivityLog::record('created', $folder);

        return redirect()->route('admin.drive.index', ['folder' => $folder->parent_id])->with('ok', 'ফোল্ডার তৈরি হয়েছে।');
    }

    /**
     * One piece of an upload. The browser sends a file in order, piece by piece, with the byte offset of each piece;
     * after the last one the file is added to the folder. Answers JSON: {offset} to continue, or {done, id}.
     */
    public function upload(Request $request)
    {
        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{8,64}$/'],
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:0', 'max:'.self::MAX_FILE],
            'offset' => ['required', 'integer', 'min:0'],
            'folder' => ['nullable', $this->folderRule()],
            'relative' => ['nullable', 'string', 'max:1000'],
            'chunk' => ['present', 'file', 'max:'.(self::CHUNK / 1024 * 2)],
        ], ['size.max' => 'একটি ফাইল ৫ GB-এর বেশি হতে পারবে না।']);
        // Form fields arrive as text; compare them as numbers.
        $data['offset'] = (int) $data['offset'];
        $data['size'] = (int) $data['size'];

        $disk = Storage::disk('local');
        $disk->makeDirectory(DriveItem::TMP);
        $part = $disk->path(DriveItem::TMP.'/'.$request->user()->id.'_'.$data['upload_id'].'.part');

        if ($data['offset'] === 0) {
            if ((int) @disk_free_space($disk->path('')) - $data['size'] < self::KEEP_FREE) {
                return response()->json(['message' => 'কম্পিউটারে যথেষ্ট জায়গা নেই। পুরনো ফাইল মুছে ট্র্যাশ খালি করুন।'], 422);
            }
            @unlink($part);
        }
        clearstatcache(true, $part);
        $have = is_file($part) ? filesize($part) : 0;
        if ($have !== $data['offset']) {
            // A piece went missing or was sent twice: tell the browser where to carry on.
            return response()->json(['offset' => $have], 409);
        }

        $in = fopen($request->file('chunk')->getRealPath(), 'rb');
        $out = fopen($part, 'ab');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
        clearstatcache(true, $part);
        $have = filesize($part);

        if ($have < $data['size']) {
            return response()->json(['offset' => $have]);
        }
        if ($have > $data['size']) {
            @unlink($part);

            return response()->json(['message' => 'ফাইলটি ঠিকমতো আসেনি, আবার চেষ্টা করুন।'], 422);
        }

        $name = $this->cleanName($data['name']);
        $ext = substr(preg_replace('/[^a-z0-9]/', '', strtolower(pathinfo($name, PATHINFO_EXTENSION))), 0, 10);
        $path = DriveItem::DIR.'/'.now()->format('Y-m').'/'.Str::uuid().($ext !== '' ? '.'.$ext : '');
        $disk->makeDirectory(dirname($path));
        rename($part, $disk->path($path));

        $item = DriveItem::create([
            'name' => $name,
            'path' => $path,
            'size' => $have,
            'mime' => $this->mimeOf($disk->path($path), $ext),
            'parent_id' => $this->folderFor($data['folder'] ?? null, $data['relative'] ?? '', $request->user()->id),
            'user_id' => $request->user()->id,
        ]);
        ActivityLog::record('created', $item);

        return response()->json(['done' => true, 'id' => $item->id]);
    }

    public function download(DriveItem $item)
    {
        return $item->is_folder ? $this->zip([$item->id], $item->name) : $item->fileResponse();
    }

    public function open(DriveItem $item)
    {
        abort_if($item->is_folder, 404);

        return $item->fileResponse($item->canOpen());
    }

    public function update(Request $request, DriveItem $item)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:200']], [], ['name' => 'নাম']);
        $name = $this->cleanName($data['name']);
        // Keep the file type when only the name part was typed.
        if (! $item->is_folder && $item->extension() !== '' && pathinfo($name, PATHINFO_EXTENSION) === '') {
            $name .= '.'.$item->extension();
        }
        $old = $item->name;
        $item->update(['name' => $name]);
        ActivityLog::record('updated', $item, ['নাম' => [$old, $name]]);

        return back()->with('ok', 'নাম বদলানো হয়েছে।');
    }

    public function star(DriveItem $item)
    {
        $item->update(['starred' => ! $item->starred]);

        return back()->with('ok', $item->starred ? 'তারকা দেওয়া হয়েছে।' : 'তারকা সরানো হয়েছে।');
    }

    /** Turn the "anyone with the link can download" link on or off. */
    public function share(Request $request, DriveItem $item)
    {
        abort_if($item->is_folder, 404);
        $item->update(['share_token' => $request->boolean('off') ? null : ($item->share_token ?? Str::random(32))]);

        return back()->with('ok', $item->share_token
            ? 'শেয়ার লিংক চালু হয়েছে — লিংক যার কাছে থাকবে সে-ই ফাইলটি ডাউনলোড করতে পারবে:<br><b style="word-break:break-all">'.e(route('drive.shared', $item->share_token)).'</b>'
            : 'শেয়ার লিংক বন্ধ করা হয়েছে।');
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
            'target' => ['nullable', $this->folderRule()],
        ], ['ids.required' => 'কোনো ফাইল বাছাই করা হয়নি।']);
        $target = $data['target'] ?? null;
        $items = DriveItem::whereIn('id', $data['ids'])->get();

        // A folder can't go inside itself.
        $blocked = $target ? [...$items->where('is_folder', true)->pluck('id')->all(), ...DriveItem::descendantIds($items->where('is_folder', true)->pluck('id')->all())] : [];
        if ($target && in_array((int) $target, $blocked, true)) {
            return back()->withErrors(['target' => 'একটি ফোল্ডারকে তার নিজের ভেতরে সরানো যায় না।']);
        }
        foreach ($items as $item) {
            $item->update(['parent_id' => $target]);
        }
        ActivityLog::record('updated', 'DriveItem', null, $items->count().'টি আইটেম সরানো হয়েছে → '.($target ? DriveItem::find($target)->name : 'আমার ড্রাইভ'));

        return back()->with('ok', bn($items->count()).'টি আইটেম সরানো হয়েছে।');
    }

    public function trash(Request $request)
    {
        $items = DriveItem::whereIn('id', $this->ids($request))->get();
        foreach ($items as $item) {
            $item->delete();
            if ($item->is_folder) {
                DriveItem::whereIn('id', DriveItem::descendantIds([$item->id]))->update(['deleted_at' => $item->deleted_at]);
            }
            ActivityLog::record('deleted', $item, null, 'ট্র্যাশে: '.$item->activityLabel());
        }

        return back()->with('ok', bn($items->count()).'টি আইটেম ট্র্যাশে গেছে। ৩০ দিন পর নিজে থেকে মুছে যাবে, তার আগে ফিরিয়ে আনা যাবে।');
    }

    public function restore(Request $request)
    {
        $items = DriveItem::onlyTrashed()->whereIn('id', $this->ids($request))->get();
        foreach ($items as $item) {
            $at = $item->deleted_at;
            DriveItem::onlyTrashed()->whereIn('id', DriveItem::descendantIds([$item->id]))->where('deleted_at', $at)->update(['deleted_at' => null]);
            $item->restore();
            // Its folder is still in the trash: bring it back to the top level instead.
            if ($item->parent_id && ! DriveItem::whereKey($item->parent_id)->exists()) {
                $item->update(['parent_id' => null]);
            }
        }

        return back()->with('ok', bn($items->count()).'টি আইটেম ফিরিয়ে আনা হয়েছে।');
    }

    public function destroy(Request $request)
    {
        $n = self::deleteForever(DriveItem::onlyTrashed()->whereIn('id', $this->ids($request))->pluck('id')->all());
        ActivityLog::record('deleted', 'DriveItem', null, 'ট্র্যাশ থেকে চিরতরে মোছা: '.$n.'টি আইটেম');

        return back()->with('ok', 'চিরতরে মুছে ফেলা হয়েছে।');
    }

    public function emptyTrash()
    {
        $n = self::deleteForever(DriveItem::onlyTrashed()->pluck('id')->all());
        ActivityLog::record('deleted', 'DriveItem', null, 'ট্র্যাশ খালি করা হয়েছে: '.$n.'টি আইটেম');

        return back()->with('ok', 'ট্র্যাশ খালি করা হয়েছে।');
    }

    /** Download several selected items as one ZIP. */
    public function zipSelected(Request $request)
    {
        return $this->zip($this->ids($request), 'drive-'.now()->format('Y-m-d'));
    }

    /** Public "anyone with the link" download. */
    public function shared(string $token)
    {
        $item = DriveItem::where('share_token', $token)->where('is_folder', false)->firstOrFail();

        return $item->fileResponse();
    }

    /** Delete rows and stored files of these items and everything inside them. Returns the number of top items. */
    public static function deleteForever(array $ids): int
    {
        if (! $ids) {
            return 0;
        }
        $all = [...$ids, ...DriveItem::descendantIds($ids)];
        $paths = DriveItem::withTrashed()->whereIn('id', $all)->whereNotNull('path')->pluck('path')->all();
        DriveItem::withTrashed()->whereIn('id', $all)->forceDelete();
        Storage::disk('local')->delete($paths);

        return count($ids);
    }

    private function zip(array $ids, string $name)
    {
        $tops = DriveItem::whereIn('id', $ids)->get();
        abort_if($tops->isEmpty(), 404);

        // relative path inside the zip => stored path, walking folders
        $entries = [];
        $walk = function ($items, string $prefix) use (&$walk, &$entries) {
            foreach ($items as $item) {
                $rel = $prefix.str_replace(['/', '\\'], '_', $item->name);
                if ($item->is_folder) {
                    $entries[$rel.'/'] = null;
                    $walk(DriveItem::where('parent_id', $item->id)->get(), $rel.'/');
                } else {
                    $base = $rel;
                    for ($i = 2; array_key_exists($rel, $entries); $i++) {
                        $rel = preg_replace('/(\.[^.]*)?$/', " ($i)$1", $base, 1);
                    }
                    $entries[$rel] = $item;
                }
            }
        };
        $walk($tops, '');

        $total = collect($entries)->filter()->sum('size');
        if ($total > self::MAX_ZIP) {
            return back()->withErrors(['zip' => 'একসাথে ৪ GB-এর বেশি ZIP করা যায় না। ফাইলগুলো আলাদা করে ডাউনলোড করুন।']);
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory(DriveItem::TMP);
        $tmp = $disk->path(DriveItem::TMP.'/'.Str::uuid().'.zip');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::CREATE);
        foreach ($entries as $rel => $item) {
            if (! $item) {
                $zip->addEmptyDir(rtrim($rel, '/'));
            } elseif ($disk->exists($item->path)) {
                $zip->addFile($disk->path($item->path), $rel);
                // Store without compressing: fast on this PC, and photos/videos don't shrink anyway.
                $zip->setCompressionName($rel, ZipArchive::CM_STORE);
            }
        }
        if ($zip->numFiles === 0) {
            $zip->addEmptyDir($name);
        }
        $zip->close();

        return response()->download($tmp, $this->cleanName($name).'.zip')->deleteFileAfterSend();
    }

    /** Find or create the folders of a folder upload ("Photos/2026") under the folder being viewed. */
    private function folderFor(?int $parent, string $relative, int $userId): ?int
    {
        foreach (array_filter(explode('/', str_replace('\\', '/', $relative)), fn ($p) => trim($p) !== '') as $part) {
            $name = $this->cleanName($part);
            $parent = DriveItem::where('parent_id', $parent)->where('is_folder', true)->where('name', $name)->value('id')
                ?? DriveItem::create(['is_folder' => true, 'name' => $name, 'parent_id' => $parent, 'user_id' => $userId])->id;
        }

        return $parent;
    }

    /** Every folder with its full path, for the "move to" list. */
    private function folderPaths(): array
    {
        $folders = DriveItem::where('is_folder', true)->get(['id', 'parent_id', 'name'])->keyBy('id');
        $path = function ($f) use (&$path, $folders) {
            return $f->parent_id && isset($folders[$f->parent_id]) ? $path($folders[$f->parent_id]).' / '.$f->name : $f->name;
        };
        $out = $folders->mapWithKeys(fn ($f) => [$f->id => $path($f)])->all();
        natcasesort($out);

        return $out;
    }

    private function folderRule()
    {
        return Rule::exists('drive_items', 'id')->where('is_folder', true)->whereNull('deleted_at');
    }

    private function ids(Request $request): array
    {
        return $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['integer']], ['ids.required' => 'কোনো ফাইল বাছাই করা হয়নি।'])['ids'];
    }

    private function sort(Request $request): array
    {
        $sort = in_array($request->query('sort'), ['name', 'updated_at', 'size'], true) ? $request->query('sort') : 'name';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        return [$sort, $dir];
    }

    private function cleanName(string $name): string
    {
        $name = trim(preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', ' ', $name));

        return mb_substr($name !== '' ? $name : 'নামহীন', 0, 200);
    }

    private function mimeOf(string $file, string $ext): ?string
    {
        // Trust the content, but only keep a browser-viewable type when the extension agrees.
        $guess = @mime_content_type($file) ?: null;
        $byExt = MimeTypes::getDefault()->getMimeTypes($ext);

        return $guess && in_array($guess, $byExt, true) ? $guess : ($byExt[0] ?? $guess);
    }
}
