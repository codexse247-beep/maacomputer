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

    public function test(Request $request)
    {
        $data = $request->validate(['to' => ['required', 'email', 'max:255']], [], ['to' => 'ইমেইল']);
        $log = Notifier::sendEmail($data['to'], setting('name').' — পরীক্ষামূলক ইমেইল', "এটি একটি পরীক্ষামূলক ইমেইল।\nএটি পেলে বুঝবেন ওয়েবসাইট থেকে ইমেইল পাঠানো ঠিকমতো কাজ করছে।\n\n".setting('name'), null, 'test');

        return $log->status === 'sent'
            ? back()->with('ok', e($data['to']).' ঠিকানায় পরীক্ষামূলক ইমেইল পাঠানো হয়েছে। ইনবক্স (বা স্প্যাম) দেখুন।')
            : back()->withErrors(['to' => 'ইমেইল যায়নি: '.($log->response ?: $log->statusLabel())]);
    }
}
