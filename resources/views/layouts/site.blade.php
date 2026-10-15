<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@hasSection('title')@yield('title') — @endif{{ setting('name') }}</title>
<meta name="description" content="{{ setting('name') }}, {{ setting('area') }} — অফিস অ্যাপ্লিকেশন, গ্রাফিক ডিজাইন, ওয়েব, ফ্রিল্যান্সিং ও নেটওয়ার্কিংয়ে হাতে-কলমে প্রশিক্ষণ।">
<meta name="theme-color" content="#0E3A05">
<link rel="icon" href="{{ asset('images/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo-192.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
@stack('head')
</head>
<body class="site">
@php
    $on = fn (string ...$routes) => request()->routeIs(...$routes) ? 'nl on' : 'nl';
    $portalUrl = auth('student')->check() ? route('portal.dashboard') : route('login');
@endphp
<header style="border-bottom:1px solid #DFE5DB;background:#fff">
<div class="wrap site-head" id="sitehead" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px 24px;min-height:88px;padding-top:8px;padding-bottom:8px">
    <a class="brand" href="{{ route('home') }}" style="display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;min-width:0;flex:1 1 0">
        <img src="{{ logo_url() }}" alt="{{ setting('name') }}-এর লোগো" style="width:56px;height:56px;border-radius:50%;object-fit:cover;flex:none">
        <span style="display:flex;flex-direction:column;min-width:0">
            <span style="font-size:clamp(16px,2.2vw,20px);font-weight:700;line-height:1.3;color:#0E3A05">{{ setting('name') }}</span>
            <span class="brand-sub" style="font-size:12px;font-weight:600;color:#55635A">{{ setting('name_en') }} · {{ setting('domain') }}</span>
        </span>
    </a>
    <button type="button" class="btn menu-btn" aria-expanded="false" aria-controls="sitehead" onclick="const h=document.getElementById('sitehead');h.classList.toggle('open');this.setAttribute('aria-expanded',h.classList.contains('open'))">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>মেনু
    </button>
    <nav class="site-nav" style="display:flex;flex-wrap:wrap;gap:4px" aria-label="প্রধান মেনু">
        <a class="{{ $on('home') }}" href="{{ route('home') }}">হোম</a>
        <a class="{{ $on('departments') }}" href="{{ route('departments') }}">বিভাগসমূহ</a>
        <a class="{{ $on('admission*') }}" href="{{ route('admission') }}">ভর্তি</a>
        <a class="{{ $on('portal.*') }}" href="{{ $portalUrl }}">স্টুডেন্ট পোর্টাল</a>
        <a class="{{ $on('notices') }}" href="{{ route('notices') }}">নোটিশ</a>
        <a class="{{ $on('contact*') }}" href="{{ route('contact') }}">যোগাযোগ</a>
    </nav>
    <div class="site-cta" style="display:flex;flex-wrap:wrap;gap:10px">
        @if (auth('student')->check())
            <a class="btn" href="{{ route('portal.dashboard') }}">আমার পোর্টাল</a>
        @else
            <a class="btn" href="{{ route('login') }}">স্টুডেন্ট লগইন</a>
        @endif
        <a class="btn p" href="{{ route('admission') }}">ভর্তি হোন</a>
    </div>
</div>
</header>

<main>
@yield('content')
</main>

<footer style="background:#0E3A05;color:#CFE3C8;padding:56px 0 32px">
<div class="wrap" style="display:flex;flex-direction:column;gap:36px">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:32px">
        <div style="display:flex;align-items:center;gap:14px;flex:1 1 280px;max-width:420px;min-width:0">
            <img src="{{ logo_url() }}" alt="" style="width:60px;height:60px;border-radius:50%;object-fit:cover;flex:none;background:#fff">
            <span style="display:flex;flex-direction:column;min-width:0"><span style="font-size:19px;font-weight:700;color:#fff">{{ setting('name') }}</span><span style="font-size:13px">{{ setting('area') }}</span></span>
        </div>
        <nav class="fl foot-nav" aria-label="ফুটার মেনু">
            <a href="{{ route('departments') }}">বিভাগসমূহ</a>
            <a href="{{ route('admission') }}">ভর্তি</a>
            <a href="{{ $portalUrl }}">স্টুডেন্ট পোর্টাল</a>
            <a href="{{ route('notices') }}">নোটিশ</a>
            <a href="{{ route('about') }}">আমাদের সম্পর্কে</a>
            <a href="{{ route('instructors') }}">শিক্ষকবৃন্দ</a>
            <a href="{{ route('gallery') }}">গ্যালারি</a>
            <a href="{{ route('faq') }}">প্রশ্নোত্তর</a>
            <a href="{{ route('verify') }}">সনদ যাচাই</a>
            <a href="{{ route('contact') }}">যোগাযোগ</a>
            <a href="{{ route('privacy') }}">গোপনীয়তা ও শর্ত</a>
        </nav>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,.15);padding-top:20px;font-size:14px">© {{ bn(now()->year) }} {{ setting('name') }}। সর্বস্বত্ব সংরক্ষিত।</div>
</div>
</footer>
@stack('scripts')
<script src="{{ asset('js/password-toggle.js') }}?v=1" defer></script>
</body>
</html>
