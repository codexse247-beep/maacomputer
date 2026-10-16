<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $thisMonth = (int) Payment::whereBetween('paid_on', [now()->startOfMonth()->toDateString(), now()->toDateString()])->sum('amount');
        $lastMonth = (int) Payment::whereBetween('paid_on', [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->toDateString()])->sum('amount');
        $today = Payment::whereDate('paid_on', now())->selectRaw('COUNT(*) as n, COALESCE(SUM(amount),0) as total')->first();

        $students = Student::with('batch.department')->withSum('payments', 'amount')
            ->whereIn('status', ['active', 'on_leave', 'completed'])
            ->when($request->integer('department'), fn ($q, $d) => $q->whereHas('batch', fn ($b) => $b->where('department_id', $d)))
            ->when($request->integer('batch'), fn ($q, $b) => $q->where('batch_id', $b))
            ->get();
        $allDue = $students->filter(fn (Student $s) => $s->totalDue() > 0);

        $fee = $request->query('fee');
        $rows = ($fee ? $students->filter(fn (Student $s) => $s->feeState() === $fee) : $students)
            ->sortBy([fn ($a, $b) => $b->totalDue() <=> $a->totalDue(), fn ($a, $b) => strcmp($a->student_code, $b->student_code)])
            ->values();

        return view('admin.payments.index', [
            'thisMonth' => $thisMonth,
            'change' => $lastMonth ? (int) round(($thisMonth - $lastMonth) / $lastMonth * 100) : null,
            'today' => $today,
            'totalDue' => $allDue->sum(fn ($s) => $s->totalDue()),
            'dueCount' => $allDue->count(),
            'rows' => $rows,
            'recent' => Payment::with('student.batch')->latest('paid_on')->latest('id')->take(10)->get(),
            'departments' => \App\Models\Department::orderBy('sort')->get(),
            'batches' => \App\Models\Batch::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_on' => ['required', 'date'],
            'item' => ['required', 'string', 'max:255'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'next_installment_date' => ['nullable', 'date'],
            'next_installment_amount' => ['nullable', 'integer', 'min:0'],
        ], [], ['amount' => 'পরিমাণ', 'paid_on' => 'তারিখ', 'item' => 'খাত', 'method' => 'মাধ্যম']);

        $payment = $student->payments()->create([
            'receipt_no' => Payment::nextReceiptNo(),
            'paid_on' => $data['paid_on'],
            'item' => $data['item'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'received_by' => $request->user()->id,
        ]);

        $student->update([
            'next_installment_date' => $data['next_installment_date'] ?? null,
            'next_installment_amount' => $data['next_installment_amount'] ?? null,
        ]);

        $student->loadSum('payments', 'amount');
        $emailed = Notifier::toStudent($student, 'payment', ['amount' => bn(number_format($payment->amount)), 'receipt' => $payment->receipt_no, 'date' => bn_date($payment->paid_on)]);

        $link = '<a href="'.e(route('admin.payments.receipt', $payment)).'" target="_blank">রসিদ প্রিন্ট করুন</a>'.($emailed ? ' · স্টুডেন্টকে ইমেইল করা হয়েছে।' : '');

        return redirect()->route('admin.students.show', $student)
            ->with('ok', 'পেমেন্ট গ্রহণ করা হয়েছে — রসিদ নং '.e($payment->receipt_no).'। '.$link);
    }

    /** Fix a wrongly entered payment (the change is kept in the activity log). */
    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_on' => ['required', 'date'],
            'item' => ['required', 'string', 'max:255'],
            'method' => ['required', Rule::in(Payment::METHODS)],
        ], [], ['amount' => 'পরিমাণ', 'paid_on' => 'তারিখ', 'item' => 'খাত', 'method' => 'মাধ্যম']);

        $payment->update($data);

        return redirect(route('admin.students.show', $payment->student_id).'#fees')->with('ok', 'রসিদ '.e($payment->receipt_no).' সংশোধন করা হয়েছে।');
    }

    public function destroy(Payment $payment)
    {
        $student = $payment->student;
        $payment->delete();

        return redirect()->route('admin.students.show', $student)->with('ok', 'পেমেন্ট মুছে ফেলা হয়েছে।');
    }
}
