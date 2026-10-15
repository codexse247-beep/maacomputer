<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CourseFile;
use App\Models\CourseVideo;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /** Classes of the student's own course and batch, with notes and watched marks. */
    public function index(Request $request)
    {
        $student = $request->user('student')->load('batch.department')->loadSum('payments', 'amount');
        $blocked = $student->status === 'dropped';
        $videos = $blocked ? collect() : CourseVideo::with('files')->forStudent($student)->ordered()->get();

        return view('portal.videos', [
            'student' => $student,
            'videos' => $videos,
            'watched' => $student->watchedVideos()->pluck('course_video_id')->flip(),
            'blocked' => $blocked,
        ]);
    }

    public function file(Request $request, CourseVideo $video)
    {
        $this->authorizeOpen($request, $video);
        abort_unless($video->isUpload() && $video->path, 404);

        return $video->fileResponse();
    }

    public function download(Request $request, CourseFile $file)
    {
        $this->authorizeOpen($request, $file->video);

        return $file->downloadResponse();
    }

    /** Mark a class watched (watched=1) or not (watched=0). */
    public function watched(Request $request, CourseVideo $video)
    {
        $student = $this->authorizeOpen($request, $video);
        $request->boolean('watched')
            ? $student->watchedVideos()->syncWithoutDetaching([$video->id])
            : $student->watchedVideos()->detach($video->id);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    private function authorizeOpen(Request $request, CourseVideo $video)
    {
        $student = $request->user('student')->load('batch')->loadSum('payments', 'amount');
        abort_unless($video->canOpen($student), 404);

        return $student;
    }
}
