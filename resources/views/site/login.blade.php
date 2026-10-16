@extends('layouts.site')

@section('title', __('স্টুডেন্ট লগইন'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('স্টুডেন্ট পোর্টাল'), 'title' => __('স্টুডেন্ট লগইন'), 'lead' => __('উপস্থিতি, ফলাফল ও ফি দেখুন')])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;justify-content:center">
    <form method="POST" action="{{ route('portal.login.store') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        @if (session('ok'))<div class="flash ok" role="status">{{ session('ok') }}</div>@endif
        @error('student_code')<div class="flash err" role="alert">{{ $message }}</div>@enderror
        <div class="ff"><label for="su">{{ __('স্টুডেন্ট আইডি') }}</label><input class="in" id="su" name="student_code" type="text" value="{{ old('student_code') }}" placeholder="{{ __('যেমন: MCT-2607-004') }}" required autocomplete="username"></div>
        <div class="ff"><label for="sp">{{ __('পাসওয়ার্ড') }}</label><input class="in" id="sp" name="password" type="password" placeholder="{{ __('পাসওয়ার্ড') }}" required autocomplete="current-password"></div>
        <label class="sw"><input type="checkbox" name="remember" value="1">{{ __('মনে রাখুন') }}</label>
        <button class="btn p" type="submit">{{ __('পোর্টালে প্রবেশ') }}</button>
        @include('partials.google-button', ['who' => 'student'])
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;font-size:15px">
            <a href="{{ route('forgot') }}">{{ __('পাসওয়ার্ড ভুলে গেছেন?') }}</a>
            <a href="{{ route('admin.login') }}" style="color:#55635A">{{ __('কেন্দ্রের কর্মী? অ্যাডমিন লগইন') }}</a>
        </div>
    </form>
</div>
</section>
@endsection
