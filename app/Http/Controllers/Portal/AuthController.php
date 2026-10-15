<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'student_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['student_code' => 'স্টুডেন্ট আইডি', 'password' => 'পাসওয়ার্ড']);

        $credentials = ['student_code' => strtoupper(trim($data['student_code'])), 'password' => $data['password']];

        if (! Auth::guard('student')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['student_code' => 'স্টুডেন্ট আইডি বা পাসওয়ার্ড সঠিক নয়।'])->onlyInput('student_code');
        }

        if (Auth::guard('student')->user()->status === 'dropped') {
            Auth::guard('student')->logout();

            return back()->withErrors(['student_code' => 'এই অ্যাকাউন্টটি সক্রিয় নয়। অফিসে যোগাযোগ করুন।']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function password(Request $request)
    {
        /** @var Student $student */
        $student = $request->user('student');
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [], ['current_password' => 'বর্তমান পাসওয়ার্ড', 'password' => 'নতুন পাসওয়ার্ড']);

        if (! Hash::check($data['current_password'], $student->password)) {
            return back()->withErrors(['current_password' => 'বর্তমান পাসওয়ার্ড সঠিক নয়।']);
        }

        $student->update(['password' => $data['password']]);

        return back()->with('ok', 'পাসওয়ার্ড পরিবর্তন হয়েছে।');
    }

    public function logout(Request $request)
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
