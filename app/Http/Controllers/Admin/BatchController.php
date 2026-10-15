<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    public function index(Request $request, Department $department)
    {
        $batches = $department->batches()->withCount('activeStudents')->latest('start_date')->get();
        $filter = $request->query('status');

        return view('admin.batches.index', [
            'department' => $department,
            'batches' => $filter ? $batches->where('status', $filter) : $batches,
            'counts' => $batches->countBy('status'),
            'total' => $batches->count(),
            'filter' => $filter,
            'activeStudents' => $batches->whereIn('status', ['running', 'enrolling'])->sum('active_students_count'),
            'openSeats' => $batches->where('status', 'enrolling')->sum(fn ($b) => max(0, $b->capacity - $b->active_students_count)),
            'enrollingCodes' => $batches->where('status', 'enrolling')->pluck('code')->implode(', '),
        ]);
    }

    /** Every batch across departments, newest first. */
    public function all(Request $request)
    {
        $filter = $request->query('status');
        $batches = Batch::with('department')->withCount('activeStudents')
            ->when($filter, fn ($q) => $q->where('status', $filter))
            ->latest('start_date')->get();

        return view('admin.batches.all', [
            'batches' => $batches,
            'filter' => $filter,
            'counts' => Batch::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
            'departments' => Department::orderBy('sort')->get(),
        ]);
    }

    public function create(Department $department)
    {
        return view('admin.batches.form', ['department' => $department, 'batch' => new Batch(['capacity' => 25, 'status' => 'enrolling'])]);
    }

    public function store(Request $request, Department $department)
    {
        $batch = $department->batches()->create($this->validated($request));

        return redirect()->route('admin.batches.show', $batch)->with('ok', 'নতুন ব্যাচ খোলা হয়েছে।');
    }

    public function show(Request $request, Batch $batch)
    {
        $batch->load('department');
        $query = $batch->students()->withSum('payments', 'amount')->withAttendance()->orderBy('roll');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name_bn', 'like', "%$q%")->orWhere('name_en', 'like', "%$q%")
                ->orWhere('student_code', 'like', "%$q%")->orWhere('phone', 'like', "%$q%"));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $students = $query->get();
        if ($fee = $request->query('fee')) {
            $students = $students->filter(fn (Student $s) => $s->feeState() === $fee)->values();
        }

        $everyone = $batch->students()->withSum('payments', 'amount')->get();
        $att = Attendance::where('batch_id', $batch->id)->where('status', '!=', 'leave')
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days")->first();

        return view('admin.batches.show', [
            'batch' => $batch,
            'students' => $students,
            'enrolled' => $everyone->where('status', 'active')->count(),
            'paidCount' => $everyone->filter(fn ($s) => $s->feeState() === 'paid')->count(),
            'dueCount' => $everyone->filter(fn ($s) => $s->feeState() !== 'paid')->count(),
            'avgAttendance' => $att && $att->total ? (int) round($att->present_days / $att->total * 100) : null,
        ]);
    }

    public function edit(Batch $batch)
    {
        return view('admin.batches.form', ['department' => $batch->department, 'batch' => $batch]);
    }

    public function update(Request $request, Batch $batch)
    {
        $batch->update($this->validated($request, $batch));

        return redirect()->route('admin.batches.show', $batch)->with('ok', 'ব্যাচের তথ্য হালনাগাদ হয়েছে।');
    }

    public function destroy(Batch $batch)
    {
        if ($batch->students()->exists()) {
            return back()->withErrors(['batch' => 'এই ব্যাচে স্টুডেন্ট আছে, তাই মোছা যাবে না। ব্যাচটি "সমাপ্ত" করে রাখুন।']);
        }
        $department = $batch->department;
        $batch->delete();

        return redirect()->route('admin.departments.show', $department)->with('ok', 'ব্যাচ মুছে ফেলা হয়েছে।');
    }

    /** CSV of the batch's students that opens in Excel (UTF-8 with BOM so Bangla shows correctly). */
    public function export(Batch $batch)
    {
        $students = $batch->students()->withSum('payments', 'amount')->orderBy('roll')->get();

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Roll', 'Student ID', 'Name (BN)', 'Name (EN)', 'Father', 'Mobile', 'Guardian mobile', 'Admission date', 'Payable', 'Paid', 'Due', 'Status']);
            foreach ($students as $s) {
                fputcsv($out, [$s->roll, $s->student_code, $s->name_bn, $s->name_en, $s->father_name, $s->phone, $s->guardian_phone,
                    $s->admission_date->format('Y-m-d'), $s->totalPayable(), $s->totalPaid(), $s->totalDue(), $s->statusLabel()]);
            }
            fclose($out);
        }, $batch->code.'-students.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validated(Request $request, ?Batch $batch = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('batches')->ignore($batch)],
            'name' => ['required', 'string', 'max:255'],
            'days' => ['nullable', 'string', 'max:255'],
            'class_time' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'room' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(array_keys(Batch::STATUSES))],
        ], [], [
            'code' => 'ব্যাচ কোড', 'name' => 'ব্যাচের নাম', 'start_date' => 'শুরুর তারিখ', 'end_date' => 'শেষের তারিখ', 'capacity' => 'আসন সংখ্যা',
        ]);
        // The teacher's name is kept on the batch too, so printed pages work without a lookup.
        $data['teacher'] = ! empty($data['teacher_id']) ? \App\Models\Teacher::find($data['teacher_id'])->name : null;

        return $data;
    }
}
