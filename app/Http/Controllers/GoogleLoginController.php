<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Sign in with Google" for staff (admin login) and students (student login).
 * Only lets someone in whose Google email matches a staff account or a student's saved email.
 * The Google client ID and secret are entered in Admin › সেটিংস › Google লগইন.
 */
class GoogleLoginController extends Controller
{
    public static function enabled(): bool
    {
        $s = Setting::values();

        return filled($s['google_client_id'] ?? null) && filled($s['google_client_secret'] ?? null);
    }

    public function redirect(Request $request, string $who)
    {
        abort_unless(self::enabled(), 404);
        $request->session()->put('google_login_for', $who);

        return $this->driver()->scopes(['openid', 'email', 'profile'])->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request)
    {
        abort_unless(self::enabled(), 404);
        $who = $request->session()->pull('google_login_for', 'student');
        $back = $who === 'staff' ? 'admin.login' : 'login';
        $field = $who === 'staff' ? 'email' : 'student_code';

        try {
            $google = $this->driver()->user();
        } catch (\Throwable $e) {
            return redirect()->route($back)->withErrors([$field => __('Google লগইন সম্পন্ন হয়নি। আবার চেষ্টা করুন।')]);
        }

        $email = mb_strtolower(trim((string) $google->getEmail()));
        // Google only gives verified emails for Gmail; refuse anything Google says is unverified.
        if ($email === '' || (($google->user['email_verified'] ?? true) === false)) {
            return redirect()->route($back)->withErrors([$field => __('Google অ্যাকাউন্টের ইমেইল যাচাই করা নেই।')]);
        }

        if ($who === 'staff') {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if (! $user) {
                return redirect()->route('admin.login')->withErrors(['email' => __(':email কোনো স্টাফ অ্যাকাউন্টের সাথে যুক্ত নয়। প্রধান অ্যাডমিনকে বলুন সেটিংসে আপনাকে এই ইমেইল দিয়ে যোগ করতে।', ['email' => $email])]);
            }
            Auth::guard('web')->login($user, true);
            $request->session()->regenerate();
            ActivityLog::record('login', $user, null, $user->activityLabel().' · Google');

            return redirect()->intended(route('admin.dashboard'));
        }

        $student = Student::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $student) {
            return redirect()->route('login')->withErrors(['student_code' => __(':email কোনো স্টুডেন্টের প্রোফাইলে নেই। আইডি ও পাসওয়ার্ড দিয়ে লগইন করে প্রোফাইলে এই ইমেইল দিন, অথবা অফিসে জানান।', ['email' => $email])]);
        }
        if ($student->status === 'dropped') {
            return redirect()->route('login')->withErrors(['student_code' => __('এই অ্যাকাউন্টটি সক্রিয় নয়। অফিসে যোগাযোগ করুন।')]);
        }
        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    /** Google driver configured from Settings (not .env), returning to /auth/google/callback. */
    private function driver()
    {
        $s = Setting::values();
        config(['services.google' => [
            'client_id' => $s['google_client_id'],
            'client_secret' => $s['google_client_secret'],
            'redirect' => route('google.callback'),
        ]]);

        return Socialite::driver('google');
    }
}
