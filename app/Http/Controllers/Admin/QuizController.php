<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** MCQ quizzes: details, questions, and everyone's scores. */
class QuizController extends Controller
{
    public function store(Request $request)
    {
        $quiz = Quiz::create($this->validated($request) + ['published' => false]);

        return redirect()->route('admin.quizzes.show', $quiz)->with('ok', 'কুইজ তৈরি হয়েছে — এখন প্রশ্ন যোগ করুন, তারপর প্রকাশ করুন।');
    }

    public function show(Quiz $quiz)
    {
        $quiz->load(['department', 'batch', 'questions', 'attempts.student.batch']);
        $students = Student::with('batch')->where('status', '!=', 'dropped')
            ->whereHas('batch', fn ($q) => $q->where('department_id', $quiz->department_id))
            ->when($quiz->batch_id, fn ($q) => $q->where('batch_id', $quiz->batch_id))
            ->orderBy('batch_id')->orderBy('roll')->get();

        return view('admin.quizzes.show', [
            'quiz' => $quiz,
            'batches' => \App\Models\Batch::where('department_id', $quiz->department_id)->orderByDesc('start_date')->get(),
            'missing' => $students->whereNotIn('id', $quiz->attempts->whereNotNull('submitted_at')->pluck('student_id')),
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $this->validated($request, $quiz);
        $data['published'] = $request->boolean('published');
        if ($data['published'] && $quiz->questions()->doesntExist()) {
            return back()->withErrors(['published' => 'অন্তত একটি প্রশ্ন যোগ করে তারপর প্রকাশ করুন।']);
        }
        $quiz->update($data);

        return back()->with('ok', $quiz->published ? 'কুইজ প্রকাশিত — স্টুডেন্টরা পোর্টালে দিতে পারবে।' : 'কুইজ সংরক্ষণ হয়েছে (প্রকাশ হয়নি)।');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()->route('admin.assignments.index', ['course' => $quiz->department_id, 'tab' => 'quiz'])->with('ok', 'কুইজ মুছে ফেলা হয়েছে।');
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'options' => ['required', 'array', 'size:4'],
            'options.*' => ['nullable', 'string', 'max:500'],
            'correct' => ['required', 'integer', 'between:0,3'],
        ], [], ['question' => 'প্রশ্ন', 'correct' => 'সঠিক উত্তর']);

        $options = array_map(fn ($o) => trim((string) $o), $data['options']);
        if (count(array_filter($options, 'strlen')) < 2) {
            return back()->withErrors(['options' => 'অন্তত দুটি উত্তর লিখুন।'])->withInput();
        }
        if ($options[$data['correct']] === '') {
            return back()->withErrors(['correct' => 'যে উত্তরটি সঠিক বেছেছেন সেটি খালি।'])->withInput();
        }
        if ($quiz->attempts()->exists()) {
            return back()->withErrors(['question' => 'স্টুডেন্টরা কুইজ দেওয়া শুরু করেছে — এখন প্রশ্ন বদলানো যাবে না।']);
        }

        $quiz->questions()->create(['question' => $data['question'], 'options' => $options, 'correct' => $data['correct'], 'sort' => $quiz->questions()->max('sort') + 1]);

        return redirect(route('admin.quizzes.show', $quiz).'#add')->with('ok', 'প্রশ্ন যোগ হয়েছে।');
    }

    public function destroyQuestion(QuizQuestion $question)
    {
        if (Quiz::find($question->quiz_id)?->attempts()->exists()) {
            return back()->withErrors(['question' => 'স্টুডেন্টরা কুইজ দেওয়া শুরু করেছে — এখন প্রশ্ন মোছা যাবে না।']);
        }
        $question->delete();

        return back()->with('ok', 'প্রশ্ন মুছে ফেলা হয়েছে।');
    }

    /** Let one student take the quiz again (e.g. the internet dropped). */
    public function resetAttempt(Quiz $quiz, Student $student)
    {
        $quiz->attempts()->where('student_id', $student->id)->delete();

        return back()->with('ok', $student->name_bn.' আবার কুইজ দিতে পারবে।');
    }

    public function toResults(Quiz $quiz)
    {
        $done = $quiz->attempts()->whereNotNull('submitted_at')->get();
        foreach ($done as $a) {
            Assessment::updateOrCreate(
                ['student_id' => $a->student_id, 'title' => 'কুইজ: '.$quiz->title],
                ['held_on' => $a->submitted_at, 'score' => $a->score, 'max_score' => max(1, $a->total),
                    'status' => $a->score / max(1, $a->total) >= 0.4 ? 'passed' : 'failed', 'published' => true],
            );
        }

        return back()->with('ok', bn($done->count()).' জনের নম্বর ফলাফলে যোগ হয়েছে।');
    }

    private function validated(Request $request, ?Quiz $quiz = null): array
    {
        $department = $quiz?->department_id ?? $request->input('department_id');
        $data = $request->validate([
            'department_id' => [$quiz ? 'nullable' : 'required', 'exists:departments,id'],
            'batch_id' => ['nullable', Rule::exists('batches', 'id')->where('department_id', $department)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'time_limit' => ['nullable', 'integer', 'min:1', 'max:300'],
            'closes_on' => ['nullable', 'date'],
        ], [], ['department_id' => 'কোর্স', 'batch_id' => 'ব্যাচ', 'title' => 'শিরোনাম', 'time_limit' => 'সময়', 'closes_on' => 'শেষ তারিখ']);

        if ($quiz) {
            unset($data['department_id']); // a quiz stays in its course
        }
        $data['batch_id'] ??= null;
        $data['show_answers'] = $request->boolean('show_answers');

        return $data;
    }
}
