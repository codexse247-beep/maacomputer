<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Staff "forgot password": a reset link by email (needs email switched on in Settings). */
class PasswordResetController extends Controller
{
    public function request()
    {
        return view('site.staff-forgot', ['mailOn' => Notifier::enabled()]);
    }

    public function send(Request $request)
    {
        $request->validate(['email' => ['required', 'email']], [], ['email' => __('ইমেইল')]);
        if (! Notifier::configureMail()) {
            return back()->withErrors(['email' => __('ওয়েবসাইটের ইমেইল এখনো চালু হয়নি। প্রধান অ্যাডমিনকে পাসওয়ার্ড রিসেট করতে বলুন।')]);
        }

        Password::broker('users')->sendResetLink($request->only('email'));

        // Same answer whether or not the address exists, so the form can't be used to find staff emails.
        return back()->with('ok', __('এই ইমেইলে কোনো স্টাফ অ্যাকাউন্ট থাকলে পাসওয়ার্ড বদলানোর লিংক পাঠানো হয়েছে। ৬০ মিনিটের মধ্যে লিংকটি খুলুন (স্প্যাম ফোল্ডারও দেখুন)।'));
    }

    public function form(Request $request, string $token)
    {
        return view('site.staff-reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], ['email' => __('ইমেইল'), 'password' => __('নতুন পাসওয়ার্ড')]);

        $status = Password::broker('users')->reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __('লিংকটি আর কাজ করছে না (মেয়াদ শেষ বা ভুল ইমেইল)। আবার "পাসওয়ার্ড ভুলে গেছেন" থেকে লিংক নিন।')])->withInput($request->only('email'));
        }

        return redirect()->route('admin.login')->with('ok', __('পাসওয়ার্ড বদলানো হয়েছে। নতুন পাসওয়ার্ড দিয়ে লগইন করুন।'));
    }
}
