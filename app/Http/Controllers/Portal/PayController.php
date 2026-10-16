<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Student reports a bKash / Nagad / Rocket payment; the office checks the TrxID and approves it. */
class PayController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user('student')->loadSum('payments', 'amount');

        return view('portal.pay', [
            'student' => $student,
            'methods' => OnlinePayment::available(),
            'history' => OnlinePayment::with('payment')->where('student_id', $student->id)->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $student = $request->user('student')->loadSum('payments', 'amount');
        $due = $student->totalDue();
        abort_if($due <= 0 || $student->status === 'dropped', 403);
        // TrxIDs are stored in capitals, so "abc123" and "ABC123" count as the same one.
        $request->merge(['trx_id' => strtoupper(trim((string) $request->input('trx_id')))]);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(OnlinePayment::available()))],
            'amount' => ['required', 'integer', 'min:1', 'max:'.$due],
            'sender' => ['required', 'regex:/^\+?[0-9\- ]{11,16}$/'],
            'trx_id' => ['required', 'alpha_num:ascii', 'min:6', 'max:30', 'unique:online_payments,trx_id'],
        ], [
            'sender.regex' => 'যে নম্বর থেকে টাকা পাঠিয়েছেন সেটি সঠিকভাবে লিখুন।',
            'trx_id.unique' => 'এই ট্রানজেকশন আইডি আগেই জমা দেওয়া হয়েছে।',
            'amount.max' => 'বকেয়ার (৳'.bn(number_format($due)).') বেশি দেওয়া যাবে না।',
        ], ['method' => 'মাধ্যম', 'amount' => 'পরিমাণ', 'sender' => 'পাঠানোর নম্বর', 'trx_id' => 'ট্রানজেকশন আইডি']);

        OnlinePayment::create($data + ['student_id' => $student->id]);

        return redirect()->route('portal.pay')->with('ok', 'ধন্যবাদ! আপনার পেমেন্ট যাচাইয়ের জন্য জমা হয়েছে। অফিস মিলিয়ে নিলে রসিদ এখানে দেখা যাবে।');
    }
}
