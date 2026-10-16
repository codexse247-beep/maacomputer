<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\User;
use App\Support\Notifier;
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
            'area_en' => $text, 'address_en' => $text, 'hours_en' => $text, 'privacy_date_en' => $text,
            'about_intro_en' => ['nullable', 'string', 'max:2000'], 'mission_en' => ['nullable', 'string', 'max:2000'],
            'vision_en' => ['nullable', 'string', 'max:2000'], 'why_us_en' => ['nullable', 'string', 'max:2000'], 'privacy_en' => ['nullable', 'string', 'max:20000'],
            'fees' => ['array'], 'fees.*.course_fee' => ['nullable', 'integer', 'min:0'], 'fees.*.admission_fee' => ['nullable', 'integer', 'min:0'],
        ], ['starts_with' => 'গুগল ম্যাপের "Embed a map" লিংক দিন (https://www.google.com/maps/embed… দিয়ে শুরু)।'], [
            'name' => 'প্রতিষ্ঠানের নাম', 'map_url' => 'ম্যাপ লিংক', 'fees.*.course_fee' => 'কোর্স ফি', 'fees.*.admission_fee' => 'ভর্তি ফি',
        ]);

        // Online payment numbers, email account and email texts (only the fields the form sent).
        $data += $request->validate([
            'bkash_number' => ['nullable', 'regex:/^01[3-9][0-9]{8}$/'], 'nagad_number' => ['nullable', 'regex:/^01[3-9][0-9]{8}$/'],
            'rocket_number' => ['nullable', 'regex:/^01[3-9][0-9]{8,9}$/'], 'pay_note' => ['nullable', 'string', 'max:1000'],
            'mail_host' => $text, 'mail_port' => ['nullable', 'integer', 'between:1,65535'], 'mail_username' => ['nullable', 'email', 'max:255'],
            'mail_password' => $text, 'mail_from' => ['nullable', 'email', 'max:255'], 'due_days_before' => ['nullable', 'integer', 'between:0,15'],
            'google_client_id' => ['nullable', 'string', 'max:255', 'regex:/^[0-9a-z\-]+\.apps\.googleusercontent\.com$/i'], 'google_client_secret' => $text,
            ...collect(Notifier::EVENTS)->keys()->mapWithKeys(fn ($e) => ['tpl_'.$e => ['nullable', 'string', 'max:3000']])->all(),
        ], ['regex' => ':attribute ১১ ডিজিটের মোবাইল নম্বর হতে হবে (01XXXXXXXXX)।'], [
            'google_client_id' => 'Client ID', 'bkash_number' => 'বিকাশ নম্বর', 'nagad_number' => 'নগদ নম্বর', 'rocket_number' => 'রকেট নম্বর', 'mail_username' => 'ইমেইল ঠিকানা', 'mail_from' => 'প্রেরকের ইমেইল',
        ]);
        foreach (['mail_enabled', 'due_auto', ...collect(Notifier::EVENTS)->keys()->map(fn ($e) => 'notify_'.$e)] as $flag) {
            if ($request->has($flag)) {
                $data[$flag] = $request->boolean($flag) ? '1' : '0';
            }
        }
        // A blank password / secret box keeps the saved one.
        foreach (['mail_password', 'google_client_secret'] as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            }
        }
        if ($request->boolean('google_remove')) {
            $data['google_client_id'] = $data['google_client_secret'] = '';
        }

        foreach ($data['fees'] ?? [] as $id => $fee) {
            Department::whereKey($id)->update(['course_fee' => (int) ($fee['course_fee'] ?? 0), 'admission_fee' => (int) ($fee['admission_fee'] ?? 0)]);
        }
        unset($data['fees']);

        $old = Setting::values();
        // Browsers send textarea line breaks as \r\n; store \n so untouched texts don't count as changed.
        $data = array_map(fn ($v) => str_replace("\r\n", "\n", (string) ($v ?? '')), $data);
        $changed = collect($data)->filter(fn ($v, $k) => (string) ($old[$k] ?? '') !== (string) $v)
            ->map(fn ($v, $k) => in_array($k, Setting::SECRETS, true) ? ['••••', '••••'] : [mb_substr((string) ($old[$k] ?? ''), 0, 80), mb_substr((string) $v, 0, 80)]);
        Setting::put($data);
        if ($changed->isNotEmpty()) {
            ActivityLog::record('updated', 'Setting', $changed->all(), $changed->keys()->implode(', '));
        }

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

        Faq::create($data + ['sort' => $data['sort'] ?? Faq::max('sort') + 1, 'en' => Faq::englishInput($request, ['question', 'answer'])]);

        return redirect(route('admin.settings.index').'#faq')->with('ok', 'প্রশ্নোত্তর যোগ হয়েছে।');
    }

    public function destroyFaq(Faq $faq)
    {
        $faq->delete();

        return redirect(route('admin.settings.index').'#faq')->with('ok', 'প্রশ্নোত্তর মুছে ফেলা হয়েছে।');
    }
}
