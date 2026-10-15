<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public const REPORTS = [
        'students' => ['স্টুডেন্ট তালিকা', 'বিভাগ ও ব্যাচ অনুযায়ী সব স্টুডেন্ট'],
        'fees' => ['ফি আদায় ও বকেয়া', 'নির্বাচিত সময়ের আদায় ও বর্তমান বকেয়া'],
        'attendance' => ['উপস্থিতি রিপোর্ট', 'স্টুডেন্টভিত্তিক হাজিরার হার'],
        'results' => ['ফলাফল সারাংশ', 'পরীক্ষা অনুযায়ী গড় ও পাসের হার'],
        'admissions' => ['ভর্তি রিপোর্ট', 'নির্বাচিত সময়ের নতুন ভর্তি'],
        'certificates' => ['সনদ তালিকা', 'সনদপ্রাপ্ত স্টুডেন্ট'],
    ];

    public const PERIODS = ['this_month' => 'এই মাস', 'last_month' => 'গত মাস', '3_months' => 'গত ৩ মাস', 'this_year' => 'এই বছর', 'all' => 'সব সময়'];

    public function index(Request $request)
    {
        $department = $request->integer('department') ?: null;
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonthsNoOverflow($i));
        $bars = $months->map(function (Carbon $m) use ($department) {
            $n = Student::whereBetween('admission_date', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])
                ->when($department, fn ($q) => $q->whereHas('batch', fn ($b) => $b->where('department_id', $department)))->count();

            return ['month' => bn_date($m, false), 'label' => ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'][$m->month - 1], 'n' => $n];
        });

        return view('admin.reports.index', [
            'bars' => $bars,
            'max' => max(1, $bars->max('n')),
            'departments' => Department::orderBy('sort')->get(),
            'period' => array_key_exists((string) $request->query('period'), self::PERIODS) ? $request->query('period') : 'this_month',
            'department' => $department,
        ]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless(array_key_exists($report, self::REPORTS), 404);

        [$from, $to] = $this->range($request->query('period', 'all'));
        $department = $request->integer('department') ?: null;
        $batch = $request->integer('batch') ?: null;
        [$headers, $rows, $total] = $this->{'build'.ucfirst($report)}($from, $to, $department, $batch, (string) $request->query('exam'));

        $title = self::REPORTS[$report][0];
        $subtitle = collect([
            self::PERIODS[$request->query('period', 'all')] ?? null,
            $department ? Department::find($department)?->name : null,
            $batch ? Batch::find($batch)?->code : null,
            $request->query('exam') ?: null,
        ])->filter()->implode(' · ');

        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows, $total) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers);
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                if ($total) {
                    fputcsv($out, $total);
                }
                fclose($out);
            }, $report.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('print.report', compact('title', 'subtitle', 'headers', 'rows', 'total'));
    }

    private function range(string $period): array
    {
        return match ($period) {
            'this_month' => [now()->startOfMonth(), now()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '3_months' => [now()->subMonthsNoOverflow(3)->startOfDay(), now()],
            'this_year' => [now()->startOfYear(), now()],
            default => [null, null],
        };
    }

    private function students(?int $department, ?int $batch)
    {
        return Student::with('batch.department')
            ->when($department, fn ($q) => $q->whereHas('batch', fn ($b) => $b->where('department_id', $department)))
            ->when($batch, fn ($q) => $q->where('batch_id', $batch))
            ->orderBy('batch_id')->orderBy('roll');
    }

    private function buildStudents($from, $to, $department, $batch, $exam): array
    {
        $rows = $this->students($department, $batch)->get()->map(fn (Student $s) => [
            $s->student_code, bn($s->roll), $s->name_bn, $s->name_en, $s->father_name, $s->phone, $s->guardian_phone,
            $s->batch->department->name, $s->batch->code, bn_date($s->admission_date), $s->statusLabel(),
        ]);

        return [['আইডি', 'রোল', 'নাম', 'Name', 'পিতার নাম', 'মোবাইল', 'অভিভাবকের মোবাইল', 'বিভাগ', 'ব্যাচ', 'ভর্তির তারিখ', 'অবস্থা'], $rows, null];
    }

    private function buildFees($from, $to, $department, $batch, $exam): array
    {
        $students = $this->students($department, $batch)->withSum('payments', 'amount')
            ->withSum(['payments as period_paid' => fn ($q) => $from ? $q->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()]) : $q], 'amount')
            ->get();
        $rows = $students->map(fn (Student $s) => [
            $s->student_code, $s->name_bn, $s->batch->code, $s->totalPayable(), (int) $s->period_paid, $s->totalPaid(), $s->totalDue(),
            $s->totalDue() && $s->next_installment_date ? bn_date($s->next_installment_date) : '', $s->feeLabel(),
        ]);
        $total = ['', 'মোট', '', $students->sum(fn ($s) => $s->totalPayable()), (int) $students->sum('period_paid'), $students->sum(fn ($s) => $s->totalPaid()), $students->sum(fn ($s) => $s->totalDue()), '', ''];

        return [['আইডি', 'নাম', 'ব্যাচ', 'মোট প্রদেয় (৳)', 'এই সময়ে আদায় (৳)', 'মোট পরিশোধিত (৳)', 'বকেয়া (৳)', 'পরবর্তী কিস্তি', 'অবস্থা'], $rows, $total];
    }

    private function buildAttendance($from, $to, $department, $batch, $exam): array
    {
        $students = $this->students($department, $batch)->where('status', 'active')->get();
        $stats = Attendance::whereIn('student_id', $students->pluck('id'))
            ->when($from, fn ($q) => $q->whereBetween('date', [$from->toDateString(), $to->toDateString()]))
            ->selectRaw("student_id, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) p, SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) a, SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) l")
            ->groupBy('student_id')->get()->keyBy('student_id');
        $rows = $students->map(function (Student $s) use ($stats) {
            $r = $stats->get($s->id);
            $p = (int) ($r->p ?? 0);
            $a = (int) ($r->a ?? 0);

            return [$s->student_code, $s->name_bn, $s->batch->code, $p + $a, $p, $a, (int) ($r->l ?? 0), $p + $a ? round($p / ($p + $a) * 100).'%' : '—'];
        });

        return [['আইডি', 'নাম', 'ব্যাচ', 'মোট ক্লাস', 'উপস্থিত', 'অনুপস্থিত', 'ছুটি', 'হার'], $rows, null];
    }

    private function buildResults($from, $to, $department, $batch, $exam): array
    {
        $ids = $this->students($department, $batch)->pluck('students.id');

        if ($exam !== '') {
            // Marks sheet for one exam
            $rows = Assessment::with('student.batch')->whereIn('student_id', $ids)->where('title', $exam)->get()
                ->sortBy(fn ($a) => $a->student->roll)
                ->map(fn ($a) => [$a->student->student_code, $a->student->name_bn, $a->student->batch->code, $a->score ?? '', $a->max_score, $a->grade()[0], $a->statusLabel(), $a->remark ?? '']);

            return [['আইডি', 'নাম', 'ব্যাচ', 'প্রাপ্ত নম্বর', 'পূর্ণমান', 'গ্রেড', 'অবস্থা', 'মন্তব্য'], $rows->values(), null];
        }

        $rows = Assessment::with('student.batch')->whereIn('student_id', $ids)->whereNotNull('score')
            ->when($from, fn ($q) => $q->whereBetween('held_on', [$from->toDateString(), $to->toDateString()]))
            ->get()->groupBy(fn ($a) => $a->student->batch->code.'|'.$a->title)
            ->map(function ($group, $key) {
                [$code, $title] = explode('|', $key, 2);
                $pct = $group->map(fn ($a) => $a->percent());

                return [$code, $title, $group->count(), round($pct->avg(), 1).'%', round($pct->max(), 1).'%', round($group->where('status', 'passed')->count() / $group->count() * 100).'%'];
            })->values();

        return [['ব্যাচ', 'পরীক্ষা', 'পরীক্ষার্থী', 'গড়', 'সর্বোচ্চ', 'পাসের হার'], $rows, null];
    }

    private function buildAdmissions($from, $to, $department, $batch, $exam): array
    {
        $students = $this->students($department, $batch)->withSum('payments', 'amount')
            ->when($from, fn ($q) => $q->whereBetween('admission_date', [$from->toDateString(), $to->toDateString()]))
            ->reorder('admission_date', 'desc')->get();
        $rows = $students->map(fn (Student $s) => [bn_date($s->admission_date), $s->student_code, $s->name_bn, $s->phone, $s->batch->department->name, $s->batch->code, $s->admission_type, $s->reference ?? '', $s->totalPaid()]);

        return [['তারিখ', 'আইডি', 'নাম', 'মোবাইল', 'বিভাগ', 'ব্যাচ', 'ধরন', 'রেফারেন্স', 'পরিশোধিত (৳)'], $rows, ['', 'মোট', bn($students->count()).' জন', '', '', '', '', '', $students->sum(fn ($s) => $s->totalPaid())]];
    }

    private function buildCertificates($from, $to, $department, $batch, $exam): array
    {
        $rows = $this->students($department, $batch)->whereNotNull('certificate_no')
            ->when($from, fn ($q) => $q->whereBetween('certificate_issued_on', [$from->toDateString(), $to->toDateString()]))
            ->get()->map(fn (Student $s) => [$s->certificate_no, bn_date($s->certificate_issued_on), $s->student_code, $s->name_bn, $s->batch->department->course_name ?: $s->batch->department->name, $s->batch->code]);

        return [['সনদ নং', 'প্রদানের তারিখ', 'আইডি', 'নাম', 'কোর্স', 'ব্যাচ'], $rows, null];
    }
}
