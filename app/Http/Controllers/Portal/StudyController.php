<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Assignments and quizzes in the student portal. */
class StudyController extends Controller
{
    public function index(Request $request)
    {
        $student = $this->student($request);
        $blocked = $student->status === 'dropped';

        return view('portal.study', [
            'student' => $student,
            'blocked' => $blocked,
            'assignments' => $blocked ? collect() : Assignment::forStudent($student)->latest('id')->get(),
            'submissions' => AssignmentSubmission::where('student_id', $student->id)->get()->keyBy('assignment_id'),
            'quizzes' => $blocked ? collect() : Quiz::forStudent($student)->where('published', true)->withCount('questions')->latest('id')->get(),
            'attempts' => QuizAttempt::where('student_id', $student->id)->get()->keyBy('quiz_id'),
        ]);
    }

    public function assignment(Request $request, Assignment $assignment)
    {
        $student = $this->student($request);
        abort_unless($assignment->isFor($student), 404);

        return view('portal.assignment', [
            'assignment' => $assignment,
            'submission' => $assignment->submissions()->where('student_id', $student->id)->first(),
        ]);
    }

    /** Hand in (or replace, until it is marked and while the due date has not passed). */
    public function submit(Request $request, Assignment $assignment)
    {
        $student = $this->student($request);
        abort_unless($assignment->isFor($student), 404);
        $existing = $assignment->submissions()->where('student_id', $student->id)->first();
        if ($existing?->reviewed_at) {
            return back()->withErrors(['body' => 'নম্বর দেওয়া হয়ে গেছে — আর বদলানো যাবে না।']);
        }
        if ($existing && $assignment->isClosed()) {
            return back()->withErrors(['body' => 'জমার সময় শেষ — আর বদলানো যাবে না।']);
        }

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:10000', 'required_without:file'],
            'file' => ['nullable', 'file', 'extensions:'.Assignment::EXTENSIONS, 'max:20480'],
        ], ['body.required_without' => 'লিখে উত্তর দিন অথবা একটি ফাইল দিন।', 'file.extensions' => 'এই ধরনের ফাইল দেওয়া যাবে না।', 'file.max' => 'ফাইল ২০ MB-এর মধ্যে হতে হবে।'], ['body' => 'উত্তর', 'file' => 'ফাইল']);

        $values = ['body' => $data['body'] ?? null];
        if ($request->hasFile('file')) {
            $values += ['file_path' => $request->file('file')->store('submissions', 'local'), 'file_name' => $request->file('file')->getClientOriginalName()];
            if ($existing?->file_path) {
                Storage::disk('local')->delete($existing->file_path);
            }
        }
        $existing ? $existing->update($values) : $assignment->submissions()->create($values + ['student_id' => $student->id]);

        return redirect()->route('portal.assignments.show', $assignment)->with('ok', $assignment->isClosed() ? 'জমা হয়েছে (দেরিতে)।' : 'জমা হয়েছে। নম্বর দেওয়ার আগে পর্যন্ত বদলাতে পারবেন।');
    }

    public function assignmentFile(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->file_path && $assignment->isFor($this->student($request)), 404);

        return Storage::disk('local')->download($assignment->file_path, $assignment->file_name);
    }

    public function submissionFile(Request $request, AssignmentSubmission $submission)
    {
        abort_unless($submission->file_path && $submission->student_id === $this->student($request)->id, 404);

        return Storage::disk('local')->download($submission->file_path, $submission->file_name);
    }

    /** Opening a quiz starts the clock; after submitting it shows the result. */
    public function quiz(Request $request, Quiz $quiz)
    {
        $student = $this->student($request);
        abort_unless($quiz->published && $quiz->isFor($student), 404);
        $quiz->load('questions');

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $student->id)->first();
        if (! $attempt) {
            if ($quiz->isClosed() || $quiz->questions->isEmpty()) {
                return redirect()->route('portal.study')->withErrors(['quiz' => 'এই কুইজের সময় শেষ।']);
            }
            $attempt = QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $student->id, 'total' => $quiz->questions->count()]);
        }
        $attempt->setRelation('quiz', $quiz);

        return view('portal.quiz', ['quiz' => $quiz, 'attempt' => $attempt]);
    }

    public function submitQuiz(Request $request, Quiz $quiz)
    {
        $student = $this->student($request);
        abort_unless($quiz->published && $quiz->isFor($student), 404);
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $student->id)->firstOrFail();
        if ($attempt->submitted_at) {
            return redirect()->route('portal.quizzes.show', $quiz);
        }

        // Answers sent after the time limit (plus a minute of grace) are still saved; the page submits itself when time runs out.
        $answers = collect($quiz->questions)->mapWithKeys(fn ($q) => [$q->id => $request->input("answers.{$q->id}") === null ? null : (int) $request->input("answers.{$q->id}")]);
        $score = $quiz->questions->filter(fn ($q) => $answers[$q->id] === $q->correct)->count();
        $attempt->update(['answers' => $answers->all(), 'score' => $score, 'total' => $quiz->questions->count(), 'submitted_at' => now()]);

        return redirect()->route('portal.quizzes.show', $quiz)->with('ok', 'কুইজ জমা হয়েছে — আপনার নম্বর '.bn($score).'/'.bn($quiz->questions->count()).'।');
    }

    private function student(Request $request): Student
    {
        return $request->user('student')->load('batch.department');
    }
}
