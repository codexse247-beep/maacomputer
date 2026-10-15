<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $students = Student::where('status', 'active')->withSum('payments', 'amount')->get();
        $dues = $students->filter(fn (Student $s) => $s->totalDue() > 0);
        $overdue = $dues->filter(fn (Student $s) => $s->next_installment_date && $s->next_installment_date->isPast());

        $openBatches = Batch::whereIn('status', ['running', 'enrolling'])->withCount('activeStudents')->get();
        $nearlyFull = $openBatches->where('status', 'enrolling')->filter(fn ($b) => $b->capacity - $b->active_students_count <= 2);
        $endingSoon = $openBatches->where('status', 'running')->filter(fn ($b) => $b->end_date && $b->end_date->between(now(), now()->addDays(30)));
        $missingDocs = Student::where('status', 'active')->whereDoesntHave('documents', fn ($q) => $q->where('type', 'form'))->count();

        $departments = Department::withCount(['students' => fn ($q) => $q->where('students.status', 'active')])->orderBy('sort')->get();

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
            'newEnquiries' => \App\Models\Enquiry::where('status', 'new')->count(),
            'departments' => $departments,
            'maxDept' => max(1, $departments->max('students_count')),
            'recent' => Student::with('batch.department')->withSum('payments', 'amount')->latest('admission_date')->latest('id')->take(6)->get(),
        ]);
    }
}
