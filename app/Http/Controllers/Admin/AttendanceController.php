<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $batches = Batch::with('department')->where('status', '!=', 'completed')->orderBy('code')->get();
        $batch = $batches->firstWhere('id', $request->integer('batch')) ?? Batch::find($request->integer('batch')) ?? $batches->first();
        $date = $request->date('date') ?? now();

        $students = $batch ? $batch->students()->where('status', 'active')->orderBy('roll')->get() : collect();
        $marked = $batch ? Attendance::where('batch_id', $batch->id)->whereDate('date', $date)->pluck('status', 'student_id') : collect();

        $avg = null;
        if ($batch) {
            $row = Attendance::where('batch_id', $batch->id)->where('status', '!=', 'leave')->where('date', '>=', now()->subDays(30)->toDateString())
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days")->first();
            $avg = $row && $row->total ? (int) round($row->present_days / $row->total * 100) : null;
        }

        return view('admin.attendance.index', [
            'batches' => $batches,
            'batch' => $batch,
            'date' => $date,
            'students' => $students,
            'marked' => $marked,
            'avg' => $avg,
            'absentToday' => $marked->isEmpty() ? null : $marked->filter(fn ($s) => $s === 'absent')->count(),
        ]);
    }

    public function store(Request $request, Batch $batch)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['array'],
            'status.*' => [Rule::in(array_keys(Attendance::STATUSES))],
        ], [], ['date' => 'তারিখ']);

        $statuses = $data['status'] ?? [];
        $existing = Attendance::where('batch_id', $batch->id)->whereDate('date', $data['date'])->get()->keyBy('student_id');
        $present = 0;

        foreach ($batch->students()->where('status', 'active')->pluck('id') as $id) {
            $status = $statuses[$id] ?? 'absent';
            $present += $status === 'present';
            $row = $existing->get($id) ?? new Attendance(['student_id' => $id, 'date' => $data['date']]);
            $row->fill(['batch_id' => $batch->id, 'status' => $status, 'present' => $status === 'present'])->save();
        }

        return redirect()->route('admin.attendance.index', ['batch' => $batch->id, 'date' => $data['date']])
            ->with('ok', e(bn_date($data['date'])).' তারিখের হাজিরা সংরক্ষণ হয়েছে — উপস্থিত '.bn($present).' জন।');
    }

    /** Day-by-day attendance grid for the last 30 class days of a batch. */
    public function history(Batch $batch)
    {
        $records = Attendance::where('batch_id', $batch->id)->orderByDesc('date')->get();
        $dates = $records->pluck('date')->map->toDateString()->unique()->take(30)->values();
        $grid = $records->groupBy('student_id')->map(fn ($rows) => $rows->keyBy(fn ($r) => $r->date->toDateString()));

        return view('admin.attendance.history', [
            'batch' => $batch,
            'dates' => $dates,
            'grid' => $grid,
            'students' => $batch->students()->orderBy('roll')->withAttendance()->get(),
        ]);
    }
}
