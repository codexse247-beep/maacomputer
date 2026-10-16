@extends('layouts.site')

@section('title', __('নতুন পাসওয়ার্ড'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('অ্যাডমিন'), 'title' => __('নতুন পাসওয়ার্ড দিন')])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;justify-content:center">
    <form method="POST" action="{{ route('staff.password.update') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        @include('partials.site-flash')
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="ff"><label for="re">{{ __('ইমেইল') }}</label><input class="in" id="re" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="username"></div>
        <div class="ff"><label for="rp">{{ __('নতুন পাসওয়ার্ড') }}</label><input class="in" id="rp" name="password" type="password" minlength="8" required autocomplete="new-password"><small>{{ __('কমপক্ষে ৮ অক্ষর') }}</small></div>
        <div class="ff"><label for="rc">{{ __('নতুন পাসওয়ার্ড আবার') }}</label><input class="in" id="rc" name="password_confirmation" type="password" required autocomplete="new-password"></div>
        <button class="btn p" type="submit">{{ __('পাসওয়ার্ড বদলান') }}</button>
    </form>
</div>
</section>
@endsection
