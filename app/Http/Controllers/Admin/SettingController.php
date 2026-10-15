<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            's' => Setting::values(),
            'departments' => Department::orderBy('sort')->get(),
            'users' => User::orderBy('name')->get(),
            'faqs' => Faq::orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $text = ['nullable', 'string', 'max:255'];
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => $text, 'domain' => $text, 'area' => $text, 'address' => $text, 'phone' => $text, 'email' => $text, 'hours' => $text,
            'map_url' => ['nullable', 'url', 'starts_with:https://www.google.com/maps/embed', 'max:2000'],
            'about_intro' => ['nullable', 'string', 'max:2000'], 'mission' => ['nullable', 'string', 'max:2000'],
            'vision' => ['nullable', 'string', 'max:2000'], 'why_us' => ['nullable', 'string', 'max:2000'],
            'certified_count' => $text, 'privacy_date' => $text, 'privacy' => ['nullable', 'string', 'max:20000'],
            'fees' => ['array'], 'fees.*.course_fee' => ['nullable', 'integer', 'min:0'], 'fees.*.admission_fee' => ['nullable', 'integer', 'min:0'],
        ], ['starts_with' => 'গুগল ম্যাপের "Embed a map" লিংক দিন (https://www.google.com/maps/embed… দিয়ে শুরু)।'], [
            'name' => 'প্রতিষ্ঠানের নাম', 'map_url' => 'ম্যাপ লিংক', 'fees.*.course_fee' => 'কোর্স ফি', 'fees.*.admission_fee' => 'ভর্তি ফি',
        ]);

        foreach ($data['fees'] ?? [] as $id => $fee) {
            Department::whereKey($id)->update(['course_fee' => (int) ($fee['course_fee'] ?? 0), 'admission_fee' => (int) ($fee['admission_fee'] ?? 0)]);
        }
        unset($data['fees']);
        Setting::put(array_map(fn ($v) => $v ?? '', $data));

        return redirect()->route('admin.settings.index')->with('ok', 'সেটিংস সংরক্ষণ হয়েছে। নতুন ফি শুধু নতুন ভর্তিতে প্রযোজ্য হবে।');
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ], [], ['name' => 'নাম', 'email' => 'ইমেইল', 'role' => 'ভূমিকা']);

        $password = Str::password(12, symbols: false);
        User::create($data + ['password' => $password]);

        return redirect(route('admin.settings.index').'#users')->with('ok', 'ব্যবহারকারী তৈরি হয়েছে — '.e($data['email']).' · পাসওয়ার্ড <b>'.$password.'</b> (এখনই লিখে রাখুন, পরে আর দেখা যাবে না)।');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(User::ROLES))]]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'নিজের অ্যাডমিন ভূমিকা সরানো যাবে না।']);
        }

        $message = 'ভূমিকা হালনাগাদ হয়েছে।';
        $user->role = $data['role'];
        if ($request->boolean('reset_password')) {
            $password = Str::password(12, symbols: false);
            $user->password = $password;
            $message = e($user->email).' এর নতুন পাসওয়ার্ড <b>'.$password.'</b> (এখনই লিখে রাখুন)।';
        }
        $user->save();

        return redirect(route('admin.settings.index').'#users')->with('ok', $message);
    }

    public function destroyUser(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'নিজের অ্যাকাউন্ট মোছা যাবে না।']);
        }
        $user->delete();

        return redirect(route('admin.settings.index').'#users')->with('ok', 'ব্যবহারকারী মুছে ফেলা হয়েছে।');
    }

    public function storeFaq(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3000'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ], [], ['question' => 'প্রশ্ন', 'answer' => 'উত্তর']);

        Faq::create($data + ['sort' => $data['sort'] ?? Faq::max('sort') + 1]);

        return redirect(route('admin.settings.index').'#faq')->with('ok', 'প্রশ্নোত্তর যোগ হয়েছে।');
    }

    public function destroyFaq(Faq $faq)
    {
        $faq->delete();

        return redirect(route('admin.settings.index').'#faq')->with('ok', 'প্রশ্নোত্তর মুছে ফেলা হয়েছে।');
    }
}
