<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OnlinePayment;
use App\Models\Payment;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** bKash / Nagad / Rocket payments students reported from the portal — check the TrxID in the app, then approve or reject. */
class OnlinePaymentController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists((string) $request->query('status'), OnlinePayment::STATUSES) ? $request->query('status') : 'pending';

        return view('admin.online-payments.index', [
            'items' => OnlinePayment::with(['student.batch', 'reviewer', 'payment'])->where('status', $status)->latest()->paginate(30)->withQueryString(),
            'status' => $status,
            'counts' => OnlinePayment::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function approve(Request $request, OnlinePayment $online)
    {
        abort_unless($online->status === 'pending', 404);

        $payment = DB::transaction(function () use ($online, $request) {
            $payment = $online->student->payments()->create([
                'receipt_no' => Payment::nextReceiptNo(),
                'paid_on' => $online->created_at->toDateString(),
                'item' => 'অনলাইন পেমেন্ট ('.$online->methodLabel().', TrxID '.$online->trx_id.')',
                'amount' => $online->amount,
                'method' => OnlinePayment::METHODS[$online->method][1] ?? Payment::METHODS[0],
                'received_by' => $request->user()->id,
            ]);
            $online->update(['status' => 'approved', 'payment_id' => $payment->id, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

            return $payment;
        });
        ActivityLog::record('approved', $online);

        $student = $online->student->loadSum('payments', 'amount');
        Notifier::toStudent($student, 'online_approved', ['amount' => bn(number_format($online->amount)), 'receipt' => $payment->receipt_no]);

        return back()->with('ok', 'গৃহীত — রসিদ '.e($payment->receipt_no).' তৈরি হয়েছে।');
    }

    public function reject(Request $request, OnlinePayment $online)
    {
        abort_unless($online->status === 'pending', 404);
        $data = $request->validate(['note' => ['required', 'string', 'max:255']], [], ['note' => 'বাতিলের কারণ']);

        $online->update(['status' => 'rejected', 'note' => $data['note'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        ActivityLog::record('rejected', $online);
        Notifier::toStudent($online->student->loadSum('payments', 'amount'), 'online_rejected', ['trx' => $online->trx_id, 'reason' => $data['note'], 'amount' => bn(number_format($online->amount))]);

        return back()->with('ok', 'বাতিল করা হয়েছে।');
    }
}
