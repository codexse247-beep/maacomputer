@extends('layouts.site')

@section('title', __('লগইন'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('লগইন'), 'title' => __('আপনার অ্যাকাউন্টে প্রবেশ করুন')])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;flex-wrap:wrap;gap:32px;justify-content:center">
    @if (session('ok'))<div class="flash ok" role="status" style="flex:1 1 100%;max-width:1072px">{{ session('ok') }}</div>@endif
    <form method="POST" action="{{ route('portal.login.store') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        <div><h2 style="margin:0;font-size:24px;color:#0E3A05">{{ __('স্টুডেন্ট লগইন') }}</h2><p style="margin:6px 0 0;font-size:16px;color:#55635A">{{ __('উপস্থিতি, ফলাফল ও ফি দেখুন') }}</p></div>
        @error('student_code')<div class="flash err" role="alert">{{ $message }}</div>@enderror
        <div class="ff"><label for="su">{{ __('স্টুডেন্ট আইডি') }}</label><input class="in" id="su" name="student_code" type="text" value="{{ old('student_code') }}" placeholder="{{ __('যেমন: MCT-2607-004') }}" required autocomplete="username"></div>
        <div class="ff"><label for="sp">{{ __('পাসওয়ার্ড') }}</label><input class="in" id="sp" name="password" type="password" placeholder="{{ __('পাসওয়ার্ড') }}" required autocomplete="current-password"></div>
        <label class="sw"><input type="checkbox" name="remember" value="1">{{ __('মনে রাখুন') }}</label>
        <button class="btn p" type="submit">{{ __('পোর্টালে প্রবেশ') }}</button>
        <a href="{{ route('forgot') }}" style="font-size:15px">{{ __('পাসওয়ার্ড ভুলে গেছেন?') }}</a>
    </form>

    <form method="POST" action="{{ route('admin.login.store') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        <div><h2 style="margin:0;font-size:24px;color:#0E3A05">{{ __('অ্যাডমিন লগইন') }}</h2><p style="margin:6px 0 0;font-size:16px;color:#55635A">{{ __('শুধু কেন্দ্রের কর্মীদের জন্য') }}</p></div>
        @error('email')<div class="flash err" role="alert">{{ $message }}</div>@enderror
        <div class="ff"><label for="au">{{ __('ইমেইল') }}</label><input class="in" id="au" name="email" type="email" value="{{ old('email') }}" placeholder="{{ __('ইমেইল') }}" required autocomplete="username"></div>
        <div class="ff"><label for="ap">{{ __('পাসওয়ার্ড') }}</label><input class="in" id="ap" name="password" type="password" placeholder="{{ __('পাসওয়ার্ড') }}" required autocomplete="current-password"></div>
        <label class="sw"><input type="checkbox" name="remember" value="1">{{ __('মনে রাখুন') }}</label>
        <button class="btn p" type="submit">{{ __('ড্যাশবোর্ডে প্রবেশ') }}</button>
        <a href="{{ route('staff.password.request') }}" style="font-size:15px">{{ __('পাসওয়ার্ড ভুলে গেছেন?') }}</a>
    </form>
</div>
</section>
@endsection
