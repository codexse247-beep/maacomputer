<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0E3A05">
<title>@yield('title') — স্টুডেন্ট পোর্টাল</title>
<link rel="icon" href="{{ asset('images/favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
@stack('head')
</head>
<body class="site" style="background:#F5F7F3;font-size:16px">
@php
    $student = auth('student')->user();
    $dash = request()->routeIs('portal.dashboard');
    $link = fn ($hash) => $dash ? '#'.$hash : route('portal.dashboard').'#'.$hash;
@endphp
<header style="background:#fff;border-bottom:1px solid #DFE5DB">
<div class="wrap site-head" id="sitehead" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 24px;min-height:76px;padding-top:8px;padding-bottom:8px">
    <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;flex:1 1 auto">
        <img src="{{ logo_url() }}" alt="{{ setting('name') }}-এর লোগো" style="width:48px;height:48px;border-radius:50%;object-fit:cover">
        <span style="display:flex;flex-direction:column;line-height:1.3"><span style="font-size:18px;font-weight:700;color:#0E3A05">মা কম্পিউটার</span><span style="font-size:13px;color:#55635A">স্টুডেন্ট পোর্টাল</span></span>
    </a>
    <button type="button" class="btn menu-btn" aria-expanded="false" aria-controls="sitehead" onclick="const h=document.getElementById('sitehead');h.classList.toggle('open');this.setAttribute('aria-expanded',h.classList.contains('open'))">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>মেনু
    </button>
    <nav class="site-nav" aria-label="পোর্টাল মেনু" style="display:flex;flex-wrap:wrap;gap:4px">
        <a class="nl {{ $dash ? 'on' : '' }}" href="{{ $link('top') }}">ড্যাশবোর্ড</a>
        <a class="nl" href="{{ $link('course') }}">আমার কোর্স</a>
        <a class="nl {{ request()->routeIs('portal.videos') ? 'on' : '' }}" href="{{ route('portal.videos') }}">ক্লাস ভিডিও</a>
        <a class="nl {{ request()->routeIs('portal.study', 'portal.assignments.*', 'portal.quizzes.*') ? 'on' : '' }}" href="{{ route('portal.study') }}">অ্যাসাইনমেন্ট ও কুইজ</a>
        <a class="nl {{ request()->routeIs('portal.calendar') ? 'on' : '' }}" href="{{ route('portal.calendar') }}">ক্যালেন্ডার</a>
        <a class="nl {{ request()->routeIs('portal.pay') ? 'on' : '' }}" href="{{ \App\Models\OnlinePayment::available() ? route('portal.pay') : $link('fees') }}">ফি ও রসিদ</a>
        <a class="nl" href="{{ $link('results') }}">ফলাফল</a>
        <a class="nl" href="{{ $link('notices') }}">নোটিশ</a>
        <a class="nl {{ request()->routeIs('portal.profile') ? 'on' : '' }}" href="{{ route('portal.profile') }}">প্রোফাইল</a>
    </nav>
    <div class="site-cta" style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('portal.profile') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit">
            @if ($student->photoUrl())<img class="av" src="{{ $student->photoUrl() }}" alt="">@else<span class="av">{{ initial($student->name_bn) }}</span>@endif
            <span style="font-weight:600">{{ $student->name_bn }}</span>
        </a>
        <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn">লগআউট</button></form>
    </div>
</div>
</header>

<main id="top" class="wrap" style="padding-top:32px;padding-bottom:56px;display:flex;flex-direction:column;gap:24px">
    @include('partials.site-flash')
    @yield('content')
</main>
<script src="{{ asset('js/password-toggle.js') }}?v=1" defer></script>
@stack('scripts')
</body>
</html>
