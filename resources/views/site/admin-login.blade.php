@extends('layouts.site')

@section('title', __('অ্যাডমিন লগইন'))

@push('head')
<meta name="robots" content="noindex">
@endpush

@section('content')
@include('partials.page-hero', ['eyebrow' => __('অ্যাডমিন'), 'title' => __('অ্যাডমিন লগইন'), 'lead' => __('শুধু কেন্দ্রের কর্মীদের জন্য')])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;justify-content:center">
    <form method="POST" action="{{ route('admin.login.store') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        @if (session('ok'))<div class="flash ok" role="status">{{ session('ok') }}</div>@endif
        @error('email')<div class="flash err" role="alert">{{ $message }}</div>@enderror
        <div class="ff"><label for="au">{{ __('ইমেইল') }}</label><input class="in" id="au" name="email" type="email" value="{{ old('email') }}" placeholder="{{ __('ইমেইল') }}" required autofocus autocomplete="username"></div>
        <div class="ff"><label for="ap">{{ __('পাসওয়ার্ড') }}</label><input class="in" id="ap" name="password" type="password" placeholder="{{ __('পাসওয়ার্ড') }}" required autocomplete="current-password"></div>
        <label class="sw"><input type="checkbox" name="remember" value="1">{{ __('মনে রাখুন') }}</label>
        <button class="btn p" type="submit">{{ __('ড্যাশবোর্ডে প্রবেশ') }}</button>
        @include('partials.google-button', ['who' => 'staff'])
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;font-size:15px">
            <a href="{{ route('staff.password.request') }}">{{ __('পাসওয়ার্ড ভুলে গেছেন?') }}</a>
            <a href="{{ route('login') }}">{{ __('স্টুডেন্ট লগইন') }} →</a>
        </div>
    </form>
</div>
</section>
@endsection
