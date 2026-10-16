<!doctype html>
@php
    $en = app()->getLocale() === 'en';
    $siteName = $en ? (setting('name_en') ?: setting('name')) : setting('name');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = ($pageTitle !== '' ? $pageTitle.' — ' : '').$siteName;
    $description = trim($__env->yieldContent('description')) ?: $siteName.', '.setting_t('area').' — '.__('অফিস অ্যাপ্লিকেশন, গ্রাফিক ডিজাইন, ওয়েব, ফ্রিল্যান্সিং ও নেটওয়ার্কিংয়ে হাতে-কলমে প্রশিক্ষণ।');
    $canonical = url()->current();
    $otherLang = $en ? 'bn' : 'en';
@endphp
<html lang="{{ $en ? 'en' : 'bn' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
<link rel="alternate" hreflang="bn" href="{{ $canonical }}?lang=bn">
<link rel="alternate" hreflang="en" href="{{ $canonical }}?lang=en">
{{-- How the page looks when shared on Facebook, WhatsApp, Messenger --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ url(logo_url()) }}">
<meta property="og:locale" content="{{ $en ? 'en_US' : 'bn_BD' }}">
<meta name="twitter:card" content="summary">
<meta name="theme-color" content="#0E3A05">
<link rel="icon" href="{{ asset('images/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo-192.png') }}">
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'EducationalOrganization',
    'name' => setting('name'),
    'alternateName' => setting('name_en'),
    'url' => route('home'),
    'logo' => url(logo_url()),
    'telephone' => str_contains(setting('phone'), '[') ? null : setting('phone'),
    'email' => str_contains(setting('email'), '[') ? null : setting('email'),
    'address' => ['@type' => 'PostalAddress', 'addressLocality' => setting('area'), 'addressCountry' => 'BD'],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
@if ($en)<style>.site{font-family:'Plus Jakarta Sans','Hind Siliguri',sans-serif}</style>@endif
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
        <img src="{{ logo_url() }}" alt="{{ __(':name-এর লোগো', ['name' => $siteName]) }}" style="width:56px;height:56px;border-radius:50%;object-fit:cover;flex:none">
        <span style="display:flex;flex-direction:column;min-width:0">
            <span style="font-size:clamp(16px,2.2vw,20px);font-weight:700;line-height:1.3;color:#0E3A05">{{ $siteName }}</span>
            <span class="brand-sub" style="font-size:12px;font-weight:600;color:#55635A">{{ $en ? setting('name') : setting('name_en') }} · {{ setting('domain') }}</span>
        </span>
    </a>
    <button type="button" class="btn menu-btn" aria-expanded="false" aria-controls="sitehead" onclick="const h=document.getElementById('sitehead');h.classList.toggle('open');this.setAttribute('aria-expanded',h.classList.contains('open'))">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>{{ __('মেনু') }}
    </button>
    <nav class="site-nav" style="display:flex;flex-wrap:wrap;gap:4px" aria-label="{{ __('প্রধান মেনু') }}">
        <a class="{{ $on('home') }}" href="{{ route('home') }}">{{ __('হোম') }}</a>
        <a class="{{ $on('departments') }}" href="{{ route('departments') }}">{{ __('বিভাগসমূহ') }}</a>
        <a class="{{ $on('admission*') }}" href="{{ route('admission') }}">{{ __('ভর্তি') }}</a>
        <a class="{{ $on('portal.*') }}" href="{{ $portalUrl }}">{{ __('স্টুডেন্ট পোর্টাল') }}</a>
        <a class="{{ $on('notices') }}" href="{{ route('notices') }}">{{ __('নোটিশ') }}</a>
        <a class="{{ $on('contact*') }}" href="{{ route('contact') }}">{{ __('যোগাযোগ') }}</a>
    </nav>
    <div class="site-cta" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
        <a class="nl" href="{{ request()->fullUrlWithQuery(['lang' => $otherLang]) }}" hreflang="{{ $otherLang }}" lang="{{ $otherLang }}" style="font-weight:700">{{ $en ? 'বাংলা' : 'English' }}</a>
        @if (auth('student')->check())
            <a class="btn" href="{{ route('portal.dashboard') }}">{{ __('আমার পোর্টাল') }}</a>
        @else
            <a class="btn" href="{{ route('login') }}">{{ __('স্টুডেন্ট লগইন') }}</a>
        @endif
        <a class="btn p" href="{{ route('admission') }}">{{ __('ভর্তি হোন') }}</a>
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
            <span style="display:flex;flex-direction:column;min-width:0"><span style="font-size:19px;font-weight:700;color:#fff">{{ $siteName }}</span><span style="font-size:13px">{{ setting_t('area') }}</span></span>
        </div>
        <nav class="fl foot-nav" aria-label="{{ __('ফুটার মেনু') }}">
            <a href="{{ route('departments') }}">{{ __('বিভাগসমূহ') }}</a>
            <a href="{{ route('admission') }}">{{ __('ভর্তি') }}</a>
            <a href="{{ $portalUrl }}">{{ __('স্টুডেন্ট পোর্টাল') }}</a>
            <a href="{{ route('notices') }}">{{ __('নোটিশ') }}</a>
            <a href="{{ route('about') }}">{{ __('আমাদের সম্পর্কে') }}</a>
            <a href="{{ route('instructors') }}">{{ __('শিক্ষকবৃন্দ') }}</a>
            <a href="{{ route('gallery') }}">{{ __('গ্যালারি') }}</a>
            <a href="{{ route('faq') }}">{{ __('প্রশ্নোত্তর') }}</a>
            <a href="{{ route('verify') }}">{{ __('সনদ যাচাই') }}</a>
            <a href="{{ route('contact') }}">{{ __('যোগাযোগ') }}</a>
            <a href="{{ route('privacy') }}">{{ __('গোপনীয়তা ও শর্ত') }}</a>
        </nav>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,.15);padding-top:20px;font-size:14px">{{ __('© :year :name। সর্বস্বত্ব সংরক্ষিত।', ['year' => bn(now()->year), 'name' => $siteName]) }}</div>
</div>
</footer>
@stack('scripts')
<script src="{{ asset('js/password-toggle.js') }}?v=1" defer></script>
</body>
</html>
