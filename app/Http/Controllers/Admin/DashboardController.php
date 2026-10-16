<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Event;
use App\Models\Expense;
use App\Models\OnlinePayment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $students = Student::where('status', 'active')->withSum('payments', 'amount')->get();
        $dues = $students->filter(fn (Student $s) => $s->totalDue() > 0);
        $overdue = $dues->filter(fn (Student $s) => $s->next_installment_date && $s->next_installment_date->isPast());

        $openBatches = Batch::whereIn('status', ['running', 'enrolling'])->withCount('activeStudents')->get();
        $nearlyFull = $openBatches->where('status', 'enrolling')->filter(fn ($b) => $b->capacity - $b->active_students_count <= 2);
        $endingSoon = $openBatches->where('status', 'running')->filter(fn ($b) => $b->end_date && $b->end_date->between(now(), now()->addDays(30)));
        $missingDocs = Student::where('status', 'active')->whereDoesntHave('documents', fn ($q) => $q->where('type', 'form'))->count();

        $departments = Department::withCount(['students' => fn ($q) => $q->where('students.status', 'active')])->orderBy('sort')->get();

        // Last 6 months: fees collected, expenses and new admissions
        $months = collect(range(5, 0))->map(function ($i) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            $range = [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()];

            return [
                'label' => bn_date($m, false),
                'short' => ['জানু', 'ফেব্রু', 'মার্চ', 'এপ্রি', 'মে', 'জুন', 'জুলা', 'আগ', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'][$m->month - 1],
                'income' => (int) Payment::whereBetween('paid_on', $range)->sum('amount'),
                'expense' => (int) Expense::whereBetween('spent_on', $range)->sum('amount'),
                'admissions' => Student::whereBetween('admission_date', $range)->count(),
            ];
        });

        return view('admin.dashboard', [
            'activeCount' => $students->count(),
            'departmentCount' => $departments->count(),
            'openBatchCount' => $openBatches->count(),
            'openSeats' => $openBatches->sum(fn ($b) => max(0, $b->capacity - $b->active_students_count)),
            'newThisMonth' => Student::whereBetween('admission_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'totalDue' => $dues->sum(fn (Student $s) => $s->totalDue()),
            'dueCount' => $dues->count(),
            'overdueCount' => $overdue->count(),
            'missingDocs' => $missingDocs,
            'nearlyFull' => $nearlyFull->count(),
            'endingSoon' => $endingSoon->count(),
            'newEnquiries' => $user->canAccess('admissions') ? \App\Models\Enquiry::where('status', 'new')->count() : 0,
            'pendingOnline' => $user->canAccess('fees') ? OnlinePayment::where('status', 'pending')->count() : 0,
            'toMark' => $user->canAccess('classes') ? AssignmentSubmission::whereNull('reviewed_at')->count() : 0,
            'departments' => $departments,
            'maxDept' => max(1, $departments->max('students_count')),
            'months' => $months,
            'maxMoney' => max(1, $months->max('income'), $months->max('expense')),
            'maxAdmissions' => max(1, $months->max('admissions')),
            'events' => Event::with(['department', 'batch'])->where(fn ($q) => $q->where('starts_on', '>=', now()->toDateString())->orWhere('ends_on', '>=', now()->toDateString()))
                ->where('starts_on', '<=', now()->addDays(30)->toDateString())->orderBy('starts_on')->take(5)->get(),
            'recent' => Student::with('batch.department')->withSum('payments', 'amount')->latest('admission_date')->latest('id')->take(6)->get(),
        ]);
    }
}
