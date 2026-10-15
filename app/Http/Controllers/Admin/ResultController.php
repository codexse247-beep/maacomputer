<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Batch;
use Illuminate\Http\Request;

/** Enter one exam's marks for a whole batch at once. */
class ResultController extends Controller
{
    public function index(Request $request)
    {
        $batches = Batch::with('department')->orderByDesc('start_date')->get();
        $batch = $batches->firstWhere('id', $request->integer('batch')) ?? $batches->firstWhere('status', 'running') ?? $batches->first();

        $students = collect();
        $exams = collect();
        $exam = trim((string) $request->query('exam'));
        $existing = collect();

        if ($batch) {
            $students = $batch->students()->whereIn('status', ['active', 'completed'])->orderBy('roll')->get();
            $exams = Assessment::whereIn('student_id', $students->pluck('id'))->select('title')->distinct()->orderBy('title')->pluck('title');
            if ($exam === '' && ! $request->has('exam')) {
                $exam = (string) $exams->first();
            }
            if ($exam !== '') {
                $existing = Assessment::whereIn('student_id', $students->pluck('id'))->where('title', $exam)->get()->keyBy('student_id');
            }
        }

        $scored = $existing->filter(fn ($a) => $a->score !== null);
        $best = $scored->sortByDesc('score')->first();

        return view('admin.results.index', [
            'batches' => $batches,
            'batch' => $batch,
            'students' => $students,
            'exams' => $exams,
            'exam' => $exam,
            'existing' => $existing,
            'max' => (int) ($existing->first()?->max_score ?? 100),
            'heldOn' => $existing->first()?->held_on,
            'published' => $existing->isNotEmpty() && $existing->every(fn ($a) => $a->published),
            'average' => $scored->isEmpty() ? null : round($scored->avg('score'), 1),
            'best' => $best,
            'passRate' => $scored->isEmpty() ? null : (int) round($scored->where('status', 'passed')->count() / $scored->count() * 100),
            'passCount' => $scored->where('status', 'passed')->count(),
        ]);
    }

    public function store(Request $request, Batch $batch)
    {
        // Marks may be typed with Bangla digits (৮৫).
        $request->merge(['marks' => array_map(
            fn ($v) => $v === null ? null : trim(strtr((string) $v, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'])),
            (array) $request->input('marks', []),
        )]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'held_on' => ['nullable', 'date'],
            'marks' => ['array'],
            'marks.*' => ['nullable', 'integer', 'min:0'],
            'remarks' => ['array'],
            'remarks.*' => ['nullable', 'string', 'max:255'],
        ], [], ['title' => 'পরীক্ষার নাম', 'max_score' => 'পূর্ণমান', 'marks.*' => 'নম্বর']);

        $marks = array_map(fn ($v) => $v === null || $v === '' ? null : (int) $v, $data['marks'] ?? []);
        foreach ($marks as $mark) {
            if ($mark !== null && $mark > $data['max_score']) {
                return back()->withInput()->withErrors(['marks' => 'কোনো নম্বর পূর্ণমানের ('.bn($data['max_score']).') বেশি হতে পারবে না।']);
            }
        }

        $publish = $request->input('action') === 'publish';
        $ids = $batch->students()->pluck('id');

        foreach ($ids as $id) {
            if (! array_key_exists($id, $marks) && ! array_key_exists($id, $request->input('remarks', []))) {
                continue;
            }
            $score = $marks[$id] ?? null;
            $status = $score === null ? 'pending' : ($score / $data['max_score'] * 100 >= 40 ? 'passed' : 'failed');
            Assessment::updateOrCreate(
                ['student_id' => $id, 'title' => $data['title']],
                [
                    'held_on' => $data['held_on'] ?? null,
                    'score' => $score,
                    'max_score' => $data['max_score'],
                    'status' => $status,
                    'remark' => $request->input("remarks.$id"),
                    'published' => $publish,
                ],
            );
        }

        return redirect()->route('admin.results.index', ['batch' => $batch->id, 'exam' => $data['title']])
            ->with('ok', $publish ? 'ফলাফল প্রকাশ হয়েছে — স্টুডেন্টরা পোর্টালে দেখতে পাবে।' : 'খসড়া সংরক্ষণ হয়েছে। স্টুডেন্টরা এখনো দেখতে পাবে না।');
    }

    public function export(Request $request, Batch $batch)
    {
        return redirect()->route('admin.reports.show', ['report' => 'results', 'batch' => $batch->id, 'exam' => $request->query('exam'), 'format' => 'csv']);
    }
}
