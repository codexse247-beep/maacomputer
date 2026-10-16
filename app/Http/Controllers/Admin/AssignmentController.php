<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Quiz;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Homework: create, collect submissions, mark, and copy marks into results. */
class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::orderBy('sort')->get();
        $edit = $request->integer('edit') ? Assignment::find($request->integer('edit')) : null;
        $course = $departments->firstWhere('id', $edit?->department_id ?? $request->integer('course')) ?? $departments->first();

        return view('admin.assignments.index', [
            'departments' => $departments,
            'course' => $course,
            'assignment' => $edit,
            'batches' => Batch::orderByDesc('start_date')->get(),
            'assignments' => $course ? Assignment::with('batch')->withCount('submissions')->where('department_id', $course->id)->latest('id')->get() : collect(),
            'quizzes' => $course ? Quiz::with('batch')->withCount(['questions', 'attempts'])->where('department_id', $course->id)->latest('id')->get() : collect(),
            'tab' => $request->query('tab') === 'quiz' ? 'quiz' : 'assignment',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $assignment = Assignment::create($data + ['user_id' => $request->user()->id] + $this->attachment($request));

        return redirect()->route('admin.assignments.index', ['course' => $assignment->department_id])->with('ok', 'অ্যাসাইনমেন্ট দেওয়া হয়েছে।');
    }

    public function update(Request $request, Assignment $assignment)
    {
        $data = $this->validated($request) + $this->attachment($request);
        $old = $assignment->file_path;
        $assignment->update($data);
        if ($old && $old !== $assignment->file_path) {
            Storage::disk('local')->delete($old);
        }

        return redirect()->route('admin.assignments.show', $assignment)->with('ok', 'অ্যাসাইনমেন্ট হালনাগাদ হয়েছে।');
    }

    public function destroy(Assignment $assignment)
    {
        $files = array_filter([$assignment->file_path, ...$assignment->submissions()->pluck('file_path')]);
        $assignment->delete();
        Storage::disk('local')->delete($files);

        return redirect()->route('admin.assignments.index', ['course' => $assignment->department_id])->with('ok', 'অ্যাসাইনমেন্ট মুছে ফেলা হয়েছে।');
    }

    /** Submissions and the students who have not handed in yet. */
    public function show(Assignment $assignment)
    {
        $assignment->load(['department', 'batch', 'submissions.student.batch']);
        $students = Student::with('batch')->where('status', '!=', 'dropped')
            ->whereHas('batch', fn ($q) => $q->where('department_id', $assignment->department_id))
            ->when($assignment->batch_id, fn ($q) => $q->where('batch_id', $assignment->batch_id))
            ->orderBy('batch_id')->orderBy('roll')->get();

        return view('admin.assignments.show', [
            'assignment' => $assignment,
            'missing' => $students->whereNotIn('id', $assignment->submissions->pluck('student_id')),
            'eligible' => $students->count(),
        ]);
    }

    public function grade(Request $request, AssignmentSubmission $submission)
    {
        $max = $submission->assignment->max_marks;
        $data = $request->validate([
            'marks' => ['required', 'integer', 'min:0', 'max:'.$max],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ], [], ['marks' => 'নম্বর', 'feedback' => 'মন্তব্য']);
        $submission->update($data + ['reviewed_at' => now()]);

        return redirect(route('admin.assignments.show', $submission->assignment_id).'#s'.$submission->id)->with('ok', $submission->student->name_bn.' এর নম্বর সংরক্ষণ হয়েছে।');
    }

    /** Copy the marks into the students' results (মূল্যায়ন ও ফলাফল), replacing an earlier copy. */
    public function toResults(Assignment $assignment)
    {
        $marked = $assignment->submissions()->whereNotNull('marks')->get();
        foreach ($marked as $sub) {
            Assessment::updateOrCreate(
                ['student_id' => $sub->student_id, 'title' => 'অ্যাসাইনমেন্ট: '.$assignment->title],
                ['held_on' => $assignment->due_on ?? now(), 'score' => $sub->marks, 'max_score' => $assignment->max_marks,
                    'status' => $sub->marks / max(1, $assignment->max_marks) >= 0.4 ? 'passed' : 'failed', 'published' => true],
            );
        }

        return back()->with('ok', bn($marked->count()).' জনের নম্বর ফলাফলে যোগ হয়েছে।');
    }

    public function file(Assignment $assignment)
    {
        abort_unless($assignment->file_path, 404);

        return Storage::disk('local')->download($assignment->file_path, $assignment->file_name);
    }

    public function submissionFile(AssignmentSubmission $submission)
    {
        abort_unless($submission->file_path, 404);

        return Storage::disk('local')->download($submission->file_path, $submission->student->student_code.'-'.$submission->file_name);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'batch_id' => ['nullable', Rule::exists('batches', 'id')->where('department_id', $request->input('department_id'))],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'due_on' => ['nullable', 'date'],
            'max_marks' => ['required', 'integer', 'min:1', 'max:1000'],
            'file' => ['nullable', 'file', 'extensions:'.Assignment::EXTENSIONS, 'max:20480'],
        ], ['file.extensions' => 'এই ধরনের ফাইল দেওয়া যাবে না।'], [
            'department_id' => 'কোর্স', 'batch_id' => 'ব্যাচ', 'title' => 'শিরোনাম', 'due_on' => 'জমার শেষ তারিখ', 'max_marks' => 'পূর্ণ নম্বর', 'file' => 'ফাইল',
        ]);
        unset($data['file']);
        $data['batch_id'] ??= null;

        return $data;
    }

    private function attachment(Request $request): array
    {
        if (! $request->hasFile('file')) {
            return [];
        }
        $file = $request->file('file');

        return ['file_path' => $file->store('assignments', 'local'), 'file_name' => $file->getClientOriginalName()];
    }
}
