<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\Student;

class PrintController extends Controller
{
    public function receipt(Payment $payment)
    {
        $payment->load('student.batch.department', 'receiver');

        return view('admin.print.receipt', ['payment' => $payment, 'student' => $payment->student->loadSum('payments', 'amount')]);
    }

    public function idCard(Student $student)
    {
        return view('admin.print.id-card', ['student' => $student->load('batch.department')]);
    }

    public function batchIdCards(Batch $batch)
    {
        return view('print.id-cards', ['students' => $batch->students()->where('status', 'active')->with('batch.department')->orderBy('roll')->get()]);
    }

    public function admissionForm(Student $student)
    {
        return view('print.admission-form', ['student' => $student->load('batch.department')->loadSum('payments', 'amount')]);
    }
}
