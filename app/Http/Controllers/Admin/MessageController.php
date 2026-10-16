<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Support\Notifier;
use Illuminate\Http\Request;

/** Every email the site sent, plus a test button for the email settings. */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists((string) $request->query('status'), MessageLog::STATUSES) ? $request->query('status') : null;

        return view('admin.messages.index', [
            'logs' => MessageLog::with('student')->when($status, fn ($q) => $q->where('status', $status))->latest('id')->paginate(40)->withQueryString(),
            'status' => $status,
            'enabled' => Notifier::enabled(),
        ]);
    }

    /** Shows an email exactly as students receive it, filled with example values. */
    public function preview(string $event)
    {
        abort_unless(array_key_exists($event, Notifier::EVENTS) || $event === 'test', 404);
        $text = $event === 'test'
            ? "এটি একটি পরীক্ষামূলক ইমেইল।\nএটি পেলে বুঝবেন ওয়েবসাইট থেকে ইমেইল পাঠানো ঠিকমতো কাজ করছে।\n\n".setting('name')
            : Notifier::fill(setting('tpl_'.$event), [
                'center' => setting('name'), 'name' => 'তানভীর আহমেদ', 'id' => 'MCT-2607-004', 'password' => '583920', 'course' => 'গ্রাফিক ডিজাইন',
                'amount' => bn('2,000'), 'due' => bn('4,000'), 'date' => bn_date(now()->addDays(2)), 'receipt' => 'MR-0042', 'trx' => '9JK7XC2PQA',
                'reason' => 'বিকাশে টাকা পাওয়া যায়নি', 'title' => 'আগামীকাল ক্লাস বন্ধ', 'body' => 'বৃষ্টির কারণে আগামীকাল সব ক্লাস বন্ধ থাকবে।', 'link' => route('login'),
            ]);
        $title = $event === 'test' ? 'পরীক্ষামূলক ইমেইল' : Notifier::EVENTS[$event][1].($event === 'notice' ? ': আগামীকাল ক্লাস বন্ধ' : '');

        return view('emails.message', Notifier::emailData($title, $text, $event));
    }

    public function test(Request $request)
    {
        $data = $request->validate(['to' => ['required', 'email', 'max:255']], [], ['to' => 'ইমেইল']);
        $log = Notifier::sendEmail($data['to'], setting('name').' — পরীক্ষামূলক ইমেইল', "এটি একটি পরীক্ষামূলক ইমেইল।\nএটি পেলে বুঝবেন ওয়েবসাইট থেকে ইমেইল পাঠানো ঠিকমতো কাজ করছে।\n\n".setting('name'), null, 'test');

        return $log->status === 'sent'
            ? back()->with('ok', e($data['to']).' ঠিকানায় পরীক্ষামূলক ইমেইল পাঠানো হয়েছে। ইনবক্স (বা স্প্যাম) দেখুন।')
            : back()->withErrors(['to' => 'ইমেইল যায়নি: '.($log->response ?: $log->statusLabel())]);
    }
}
