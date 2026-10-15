<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\CourseFile;
use App\Models\CourseVideo;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CourseVideoController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::orderBy('sort')->get();
        $edit = $request->integer('edit') ? CourseVideo::with('files')->find($request->integer('edit')) : null;
        $course = $departments->firstWhere('id', $edit?->department_id ?? $request->integer('course')) ?? $departments->first();

        $videos = $course ? CourseVideo::with(['files', 'batch'])->where('department_id', $course->id)->ordered()->get() : collect();
        // Students of this course (not dropped) and who watched what, for the "watched" lists.
        $students = $course ? Student::with('batch')->whereHas('batch', fn ($q) => $q->where('department_id', $course->id))
            ->where('status', '!=', 'dropped')->orderBy('batch_id')->orderBy('roll')->get() : collect();
        $views = DB::table('video_views')->whereIn('course_video_id', $videos->pluck('id'))->get()->groupBy('course_video_id')
            ->map(fn ($rows) => $rows->pluck('student_id')->flip());

        return view('admin.course-videos.index', [
            'departments' => $departments,
            'course' => $course,
            'videos' => $videos,
            'video' => $edit,
            'batches' => Batch::orderByDesc('start_date')->get(),
            'students' => $students,
            'views' => $views,
            'counts' => CourseVideo::selectRaw('department_id, count(*) as n')->groupBy('department_id')->pluck('n', 'department_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $video = CourseVideo::create($data + [
            'path' => $data['type'] === 'upload' ? $request->file('video')->store('course-videos', 'local') : null,
        ]);
        $this->storeFiles($request, $video);

        return redirect()->route('admin.course-videos.index', ['course' => $video->department_id])->with('ok', 'ক্লাস যোগ হয়েছে।');
    }

    public function update(Request $request, CourseVideo $video)
    {
        $data = $this->validated($request, $video);
        $old = $video->path;

        if ($data['type'] === 'upload' && $request->hasFile('video')) {
            $data['path'] = $request->file('video')->store('course-videos', 'local');
        } elseif ($data['type'] === 'url') {
            $data['path'] = null;
        }
        $video->update($data);
        if ($old && $old !== $video->path) {
            Storage::disk('local')->delete($old);
        }
        $this->storeFiles($request, $video);

        return redirect()->route('admin.course-videos.index', ['course' => $video->department_id])->with('ok', 'ক্লাস হালনাগাদ হয়েছে।');
    }

    public function destroy(CourseVideo $video)
    {
        $video->load('files')->delete();
        $video->deleteStoredFiles();

        return redirect()->route('admin.course-videos.index', ['course' => $video->department_id])->with('ok', 'ক্লাস মুছে ফেলা হয়েছে।');
    }

    public function file(CourseVideo $video)
    {
        abort_unless($video->isUpload() && $video->path, 404);

        return $video->fileResponse();
    }

    public function download(CourseFile $file)
    {
        return $file->downloadResponse();
    }

    public function destroyFile(CourseFile $file)
    {
        $file->delete();
        Storage::disk('local')->delete($file->path);

        return back()->with('ok', 'ফাইল মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request, ?CourseVideo $video): array
    {
        $upload = $request->input('type') === 'upload';
        // A new class (or switching an edited one to "upload") needs a file; otherwise the old one stays.
        $needsFile = $upload && ! ($video?->isUpload() && $video->path);

        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'batch_id' => ['nullable', Rule::exists('batches', 'id')->where('department_id', $request->input('department_id'))],
            'class_no' => ['nullable', 'integer', 'min:1', 'max:999'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'type' => ['required', Rule::in(array_keys(CourseVideo::TYPES))],
            // MP4/WebM play in every browser; sizes in KB.
            // A file left in the hidden upload box is ignored when the type is "url".
            'video' => ['exclude_unless:type,upload', Rule::requiredIf($needsFile), 'nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:512000'],
            'url' => [Rule::requiredIf(! $upload), 'nullable', 'url:https', 'max:500'],
            'release_on' => ['nullable', 'date'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'extensions:'.CourseFile::EXTENSIONS, 'max:51200'],
        ], [
            'video.mimetypes' => 'শুধু MP4 বা WebM ভিডিও আপলোড করা যাবে।',
            'video.max' => 'ভিডিও ৫০০ MB-এর বেশি হতে পারবে না। বড় ভিডিও ইউটিউবে (Unlisted) রেখে লিংক দিন।',
            'files.*.extensions' => 'এই ধরনের ফাইল দেওয়া যাবে না (PDF, Word, Excel, PowerPoint, ZIP, PSD, AI, ছবি চলবে)।',
            'files.*.max' => 'প্রতিটি ফাইল ৫০ MB-এর মধ্যে হতে হবে।',
        ], ['department_id' => 'কোর্স', 'batch_id' => 'ব্যাচ', 'class_no' => 'ক্লাস নং', 'title' => 'শিরোনাম', 'video' => 'ভিডিও', 'url' => 'লিংক', 'release_on' => 'তারিখ']);

        unset($data['video'], $data['files']);
        $data['url'] = $upload ? null : $data['url'];
        $data['paid_only'] = $request->boolean('paid_only');

        return $data;
    }

    private function storeFiles(Request $request, CourseVideo $video): void
    {
        foreach ($request->file('files', []) as $file) {
            $video->files()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('course-files', 'local'),
                'size' => $file->getSize(),
            ]);
        }
    }
}
