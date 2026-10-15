<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user('student')
            ->load(['batch.department', 'payments' => fn ($q) => $q->latest('paid_on'), 'assessments' => fn ($q) => $q->where('published', true)->orderBy('held_on')])
            ->loadSum('payments', 'amount');

        $batch = $student->batch;
        $progress = null;
        if ($batch->start_date && $batch->end_date && $batch->end_date->gt($batch->start_date)) {
            $progress = (int) max(0, min(100, round($batch->start_date->diffInDays(now()) / $batch->start_date->diffInDays($batch->end_date) * 100)));
        }

        return view('portal.dashboard', [
            'student' => $student,
            'batch' => $batch,
            'att' => $student->attendanceStats(),
            'progress' => $progress,
            'notices' => Notice::forStudent($student)->latest('published_on')->latest('id')->take(8)->get(),
        ]);
    }

    public function profile(Request $request)
    {
        return view('portal.profile', ['student' => $request->user('student')->load('batch.department')]);
    }

    public function idCard(Request $request)
    {
        return view('print.id-cards', ['students' => collect([$request->user('student')->load('batch.department')]), 'portal' => true]);
    }

    public function receipt(Request $request, Payment $payment)
    {
        $student = $request->user('student');
        abort_unless($payment->student_id === $student->id, 404);
        $payment->load('student.batch.department', 'receiver');

        return view('print.receipt', ['payment' => $payment, 'student' => $student->loadSum('payments', 'amount'), 'portal' => true]);
    }
}
