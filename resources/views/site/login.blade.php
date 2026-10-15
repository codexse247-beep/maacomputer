@extends('layouts.site')

@section('title', 'লগইন')

@section('content')
@include('partials.page-hero', ['eyebrow' => 'লগইন', 'title' => 'আপনার অ্যাকাউন্টে প্রবেশ করুন'])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;flex-wrap:wrap;gap:32px;justify-content:center">
    <form method="POST" action="{{ route('portal.login.store') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        <div><h2 style="margin:0;font-size:24px;color:#0E3A05">স্টুডেন্ট লগইন</h2><p style="margin:6px 0 0;font-size:16px;color:#55635A">উপস্থিতি, ফলাফল ও ফি দেখুন</p></div>
        @error('student_code')<div class="flash err" role="alert">{{ $message }}</div>@enderror
        <div class="ff"><label for="su">স্টুডেন্ট আইডি</label><input class="in" id="su" name="student_code" type="text" value="{{ old('student_code') }}" placeholder="যেমন: MCT-2607-004" required autocomplete="username"></div>
        <div class="ff"><label for="sp">পাসওয়ার্ড</label><input class="in" id="sp" name="password" type="password" placeholder="পাসওয়ার্ড" required autocomplete="current-password"></div>
        <label class="sw"><input type="checkbox" name="remember" value="1">মনে রাখুন</label>
        <button class="btn p" type="submit">পোর্টালে প্রবেশ</button>
        <a href="{{ route('forgot') }}" style="font-size:15px">পাসওয়ার্ড ভুলে গেছেন?</a>
    </form>

    <form method="POST" action="{{ route('admin.login.store') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        <div><h2 style="margin:0;font-size:24px;color:#0E3A05">অ্যাডমিন লগইন</h2><p style="margin:6px 0 0;font-size:16px;color:#55635A">শুধু কেন্দ্রের কর্মীদের জন্য</p></div>
        @error('email')<div class="flash err" role="alert">{{ $message }}</div>@enderror
        <div class="ff"><label for="au">ইমেইল</label><input class="in" id="au" name="email" type="email" value="{{ old('email') }}" placeholder="ইমেইল" required autocomplete="username"></div>
        <div class="ff"><label for="ap">পাসওয়ার্ড</label><input class="in" id="ap" name="password" type="password" placeholder="পাসওয়ার্ড" required autocomplete="current-password"></div>
        <label class="sw"><input type="checkbox" name="remember" value="1">মনে রাখুন</label>
        <button class="btn p" type="submit">ড্যাশবোর্ডে প্রবেশ</button>
        <span style="font-size:15px;color:#55635A">অ্যাক্সেস না থাকলে প্রধান অ্যাডমিনকে জানান।</span>
    </form>
</div>
</section>
@endsection
