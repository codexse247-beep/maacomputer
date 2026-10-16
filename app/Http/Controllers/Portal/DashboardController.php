<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\CourseVideo;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user('student')
            ->load(['batch.department', 'payments' => fn ($q) => $q->latest('paid_on'), 'assessments' => fn ($q) => $q->where('published', true)->orderBy('held_on')])
            ->loadSum('payments', 'amount');

        $batch = $student->batch;
        $progress = null;
        if ($batch->start_date && $batch->end_date && $batch->end_date->gt($batch->start_date)) {
            $progress = (int) max(0, min(100, round($batch->start_date->diffInDays(now()) / $batch->start_date->diffInDays($batch->end_date) * 100)));
        }

        return view('portal.dashboard', [
            'student' => $student,
            'batch' => $batch,
            'att' => $student->attendanceStats(),
            'progress' => $progress,
            'notices' => Notice::forStudent($student)->latest('published_on')->latest('id')->take(8)->get(),
        ] + $this->learning($student));
    }

    /** Class videos, homework, quizzes and dates for the dashboard's "পড়াশোনা" card. */
    private function learning($student): array
    {
        if ($student->status === 'dropped') {
            return ['videoTotal' => 0, 'videoDone' => 0, 'nextVideo' => null, 'pendingAssignments' => collect(), 'openQuizzes' => collect(), 'events' => collect()];
        }
        $videos = CourseVideo::forStudent($student)->ordered()->get()->filter(fn ($v) => $v->lockReason($student) === null);
        $watched = $student->watchedVideos()->pluck('course_video_id')->flip();
        $submitted = \App\Models\AssignmentSubmission::where('student_id', $student->id)->pluck('assignment_id')->flip();
        $attempted = \App\Models\QuizAttempt::where('student_id', $student->id)->whereNotNull('submitted_at')->pluck('quiz_id')->flip();

        return [
            'videoTotal' => $videos->count(),
            'videoDone' => $videos->filter(fn ($v) => $watched->has($v->id))->count(),
            'nextVideo' => $videos->first(fn ($v) => ! $watched->has($v->id)),
            'pendingAssignments' => Assignment::forStudent($student)->where(fn ($q) => $q->whereNull('due_on')->orWhere('due_on', '>=', now()->toDateString()))
                ->get()->filter(fn ($a) => ! $submitted->has($a->id)),
            'openQuizzes' => Quiz::forStudent($student)->where('published', true)->where(fn ($q) => $q->whereNull('closes_on')->orWhere('closes_on', '>=', now()->toDateString()))
                ->get()->filter(fn ($q) => ! $attempted->has($q->id)),
            'events' => Event::forStudent($student)->where(fn ($q) => $q->where('starts_on', '>=', now()->toDateString())->orWhere('ends_on', '>=', now()->toDateString()))
                ->where('starts_on', '<=', now()->addDays(30)->toDateString())->orderBy('starts_on')->take(4)->get(),
        ];
    }

    /** Month calendar: class days from the batch routine, plus holidays, exams and events. */
    public function calendar(Request $request)
    {
        $student = $request->user('student')->load('batch.department');
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? Carbon::createFromFormat('Y-m-d', $request->query('month').'-01') : now()->startOfMonth();
        $batch = $student->batch;
        $names = [0 => 'রবি', 1 => 'সোম', 2 => 'মঙ্গল', 3 => 'বুধ', 4 => 'বৃহ', 5 => 'শুক্র', 6 => 'শনি'];

        return view('portal.calendar', [
            'student' => $student,
            'month' => $month,
            'events' => Event::forStudent($student)->between($month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString())->orderBy('starts_on')->get(),
            'upcoming' => Event::forStudent($student)->where(fn ($q) => $q->where('starts_on', '>=', now()->toDateString())->orWhere('ends_on', '>=', now()->toDateString()))->orderBy('starts_on')->take(10)->get(),
            'classDay' => fn (Carbon $d) => str_contains((string) $batch->days, $names[$d->dayOfWeek])
                && (! $batch->start_date || $d->gte($batch->start_date)) && (! $batch->end_date || $d->lte($batch->end_date)),
        ]);
    }

    /** Students may keep their own contact details and photo up to date. */
    public function updateProfile(Request $request)
    {
        $student = $request->user('student');
        $phone = ['regex:/^\+?[0-9\- ]{6,20}$/'];
        $data = $request->validate([
            'phone' => array_merge(['required'], $phone),
            'whatsapp' => array_merge(['nullable'], $phone),
            'email' => ['nullable', 'email', 'max:255'],
            'present_village' => ['required', 'string', 'max:255'],
            'present_po' => ['nullable', 'string', 'max:255'],
            'present_upazila' => ['required', 'string', 'max:255'],
            'present_district' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], ['regex' => ':attribute সঠিক মোবাইল নম্বর নয়।'], [
            'phone' => 'মোবাইল', 'whatsapp' => 'হোয়াটসঅ্যাপ', 'email' => 'ইমেইল', 'present_village' => 'গ্রাম / রোড / বাসা',
            'present_upazila' => 'উপজেলা', 'present_district' => 'জেলা', 'photo' => 'ছবি',
        ]);
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $data['photo'] = $request->file('photo')->store('photos', 'public');
            $student->documents()->updateOrCreate(['type' => 'photo'], ['path' => $data['photo'], 'original_name' => 'photo']);
        }
        $student->update($data);

        return redirect()->route('portal.profile')->with('ok', 'আপনার তথ্য হালনাগাদ হয়েছে।');
    }

    public function certificate(Request $request)
    {
        $student = $request->user('student');
        abort_unless($student->certificate_no, 404);

        return view('print.certificate', PrintController::certificateData($student));
    }

    public function profile(Request $request)
    {
        return view('portal.profile', ['student' => $request->user('student')->load('batch.department')]);
    }

    public function idCard(Request $request)
    {
        return view('print.id-cards', ['students' => collect([$request->user('student')->load('batch.department')]), 'portal' => true]);
    }

    public function receipt(Request $request, Payment $payment)
    {
        $student = $request->user('student');
        abort_unless($payment->student_id === $student->id, 404);
        $payment->load('student.batch.department', 'receiver');

        return view('print.receipt', ['payment' => $payment, 'student' => $student->loadSum('payments', 'amount'), 'portal' => true]);
    }
}
