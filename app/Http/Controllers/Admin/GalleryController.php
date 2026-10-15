<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GalleryController extends Controller
{
    public function index()
    {
        return view('admin.gallery.index', ['items' => GalleryPhoto::latest('id')->paginate(24)]);
    }

    public function store(Request $request)
    {
        $video = $request->input('type') === 'video';
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(GalleryPhoto::TYPES))],
            'photos' => [Rule::requiredIf(! $video), 'array', 'max:20'],
            'photos.*' => ['image', 'max:4096'],
            'video_url' => [Rule::requiredIf($video), 'nullable', 'string', 'max:255', function ($attr, $value, $fail) use ($video) {
                if ($video && ! GalleryPhoto::youtubeId($value)) {
                    $fail('সঠিক ইউটিউব লিংক দিন (যেমন https://www.youtube.com/watch?v=… বা https://youtu.be/…)।');
                }
            }],
            'caption' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(GalleryPhoto::CATEGORIES))],
        ], [], ['photos' => 'ছবি', 'photos.*' => 'ছবি', 'video_url' => 'ভিডিও লিংক', 'category' => 'ধরন']);

        $common = ['caption' => $data['caption'] ?? null, 'category' => $data['category']];

        if ($video) {
            GalleryPhoto::create($common + ['type' => 'video', 'youtube_id' => GalleryPhoto::youtubeId($data['video_url'])]);

            return redirect()->route('admin.gallery.index')->with('ok', 'ভিডিও যোগ হয়েছে।');
        }

        foreach ($request->file('photos') as $file) {
            GalleryPhoto::create($common + ['type' => 'image', 'path' => $file->store('gallery', 'public')]);
        }

        return redirect()->route('admin.gallery.index')->with('ok', bn(count($request->file('photos'))).'টি ছবি যোগ হয়েছে।');
    }

    public function destroy(GalleryPhoto $item)
    {
        if ($item->path) {
            Storage::disk('public')->delete($item->path);
        }
        $item->delete();

        return back()->with('ok', $item->isVideo() ? 'ভিডিও মুছে ফেলা হয়েছে।' : 'ছবি মুছে ফেলা হয়েছে।');
    }
}
