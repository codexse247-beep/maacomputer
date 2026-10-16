<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MessageLog;
use App\Models\Student;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Students with unpaid fees, by installment date, with email reminders. */
class DueController extends Controller
{
    public function index()
    {
        $students = self::dueStudents();
        $today = now()->startOfDay();

        return view('admin.dues.index', [
            'overdue' => $students->filter(fn ($s) => $s->next_installment_date && $s->next_installment_date->lt($today))->sortBy('next_installment_date'),
            'soon' => $students->filter(fn ($s) => $s->next_installment_date && $s->next_installment_date->between($today, $today->copy()->addDays(7)))->sortBy('next_installment_date'),
            'later' => $students->filter(fn ($s) => ! $s->next_installment_date || $s->next_installment_date->gt($today->copy()->addDays(7)))->sortByDesc(fn ($s) => $s->totalDue()),
            'reminded' => MessageLog::where('event', 'due')->where('status', 'sent')->whereIn('student_id', $students->pluck('id'))
                ->selectRaw('student_id, max(created_at) as at')->groupBy('student_id')->pluck('at', 'student_id'),
            'mailOn' => Notifier::enabled(),
            'total' => $students->sum(fn ($s) => $s->totalDue()),
        ]);
    }

    public function remind(Student $student)
    {
        $student->loadSum('payments', 'amount')->load('batch.department');
        abort_unless($student->totalDue() > 0, 404);

        return self::send($student)
            ? back()->with('ok', $student->name_bn.' কে রিমাইন্ডার ইমেইল পাঠানো হয়েছে।')
            : back()->withErrors(['email' => $student->name_bn.' এর ইমেইল ঠিকানা নেই — ফোন করুন।']);
    }

    /** Email everyone whose installment date has passed. */
    public function remindAll()
    {
        $overdue = self::dueStudents()->filter(fn ($s) => $s->next_installment_date && $s->next_installment_date->lt(now()->startOfDay()));
        $sent = $overdue->filter(fn ($s) => self::send($s))->count();
        ActivityLog::record('sent', 'Payment', null, 'কিস্তির রিমাইন্ডার ইমেইল — '.bn($sent).' জন');

        $noEmail = $overdue->count() - $sent;

        return back()->with('ok', bn($sent).' জনকে রিমাইন্ডার ইমেইল পাঠানো হয়েছে।'.($noEmail ? ' '.bn($noEmail).' জনের ইমেইল নেই — তাদের ফোন করুন।' : ''));
    }

    public static function dueStudents(): Collection
    {
        return Student::with('batch.department')->withSum('payments', 'amount')
            ->whereIn('status', ['active', 'on_leave'])->get()
            ->filter(fn (Student $s) => $s->totalDue() > 0)->values();
    }

    public static function send(Student $student): bool
    {
        return Notifier::toStudent($student, 'due', [
            'amount' => bn(number_format($student->next_installment_amount ?: $student->totalDue())),
            'date' => $student->next_installment_date ? bn_date($student->next_installment_date) : 'যত দ্রুত সম্ভব',
        ]);
    }
}
