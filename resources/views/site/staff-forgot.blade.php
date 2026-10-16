@extends('layouts.site')

@section('title', __('স্টাফ পাসওয়ার্ড'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('অ্যাডমিন'), 'title' => __('পাসওয়ার্ড ভুলে গেছেন?')])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;justify-content:center">
    <form method="POST" action="{{ route('staff.password.email') }}" style="flex:1 1 380px;max-width:520px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        @include('partials.site-flash')
        <p style="margin:0;font-size:16px;color:#3F4D44;line-height:1.7">{{ __('আপনার স্টাফ অ্যাকাউন্টের ইমেইল দিন। পাসওয়ার্ড বদলানোর একটি লিংক সেই ইমেইলে যাবে।') }}</p>
        @unless ($mailOn)<div class="flash err" role="alert">{{ __('ওয়েবসাইটের ইমেইল এখনো চালু হয়নি — প্রধান অ্যাডমিনকে সেটিংস থেকে আপনার পাসওয়ার্ড রিসেট করতে বলুন।') }}</div>@endunless
        <div class="ff"><label for="se">{{ __('ইমেইল') }}</label><input class="in" id="se" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"></div>
        <button class="btn p" type="submit" @disabled(! $mailOn)>{{ __('লিংক পাঠান') }}</button>
        <a href="{{ route('admin.login') }}" style="font-size:15px">← {{ __('লগইনে ফিরে যান') }}</a>
    </form>
</div>
</section>
@endsection
