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

    public function certificate(Student $student)
    {
        abort_unless($student->certificate_no, 404);

        return view('print.certificate', self::certificateData($student));
    }

    /** Shared with the student portal's own certificate page. */
    public static function certificateData(Student $student): array
    {
        $student->load(['batch.department', 'assessments' => fn ($q) => $q->where('published', true)]);
        $scores = $student->assessments->map->percent()->filter(fn ($p) => $p !== null);

        return ['student' => $student, 'grade' => grade_for($scores->isEmpty() ? null : $scores->avg())[0]];
    }

    public function admissionForm(Student $student)
    {
        return view('print.admission-form', ['student' => $student->load('batch.department')->loadSum('payments', 'amount')]);
    }
}
