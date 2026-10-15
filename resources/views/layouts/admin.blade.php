<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0E3A05">
<title>@yield('title') — মা কম্পিউটার অ্যাডমিন</title>
<link rel="icon" href="{{ asset('images/favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
@stack('head')
</head>
<body>
@php
    $nav = fn (string ...$patterns) => request()->routeIs(...$patterns) ? 'nv on' : 'nv';
    $admin = auth()->user();
    $newEnquiries = \App\Models\Enquiry::where('status', 'new')->count();
@endphp
<div class="shell" id="shell">
<div class="mbar noprint">
    <a href="{{ route('admin.dashboard') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none">
        <img src="{{ logo_url() }}" alt="" style="width:40px;height:40px;border-radius:50%;background:#fff;border:2px solid #fff">
        <span style="color:#fff;font-weight:700;font-size:16px">মা কম্পিউটার · অ্যাডমিন</span>
    </a>
    <button type="button" class="btn sm" aria-controls="sidenav" aria-expanded="false" onclick="const s=document.getElementById('shell');s.classList.toggle('open');this.setAttribute('aria-expanded',s.classList.contains('open'))">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>মেনু
    </button>
</div>
<aside class="side noprint" id="sidenav">
    <a href="{{ route('home') }}" target="_blank" style="display:flex;align-items:center;gap:12px;text-decoration:none;padding:4px 6px">
        <img src="{{ logo_url() }}" alt="মা কম্পিউটার লোগো" style="width:52px;height:52px;border-radius:50%;object-fit:cover;background:#fff;border:2px solid #fff;flex:none">
        <span style="display:flex;flex-direction:column;line-height:1.3"><span style="color:#fff;font-weight:700;font-size:17px">মা কম্পিউটার</span><span style="color:#9DBB93;font-size:13px">অ্যাডমিন প্যানেল</span></span>
    </a>
    <nav aria-label="অ্যাডমিন মেনু" style="display:flex;flex-direction:column;gap:20px">
        <div class="navgrp">
            <a class="{{ $nav('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">@include('partials.icon', ['n' => 'grid'])ড্যাশবোর্ড</a>
        </div>
        <div class="navgrp">
            <div class="ng">একাডেমিক</div>
            <a class="{{ $nav('admin.departments.index', 'admin.departments.create', 'admin.departments.edit') }}" href="{{ route('admin.departments.index') }}">@include('partials.icon', ['n' => 'layers'])বিভাগ</a>
            <a class="{{ $nav('admin.departments.show', 'admin.batches.*') }}" href="{{ route('admin.batches.all') }}">@include('partials.icon', ['n' => 'calendar'])ব্যাচ</a>
            <a class="{{ $nav('admin.students.index', 'admin.students.show', 'admin.students.edit') }}" href="{{ route('admin.students.index') }}">@include('partials.icon', ['n' => 'users'])স্টুডেন্ট</a>
            <a class="{{ $nav('admin.teachers.*') }}" href="{{ route('admin.teachers.index') }}">@include('partials.icon', ['n' => 'user'])শিক্ষক</a>
        </div>
        <div class="navgrp">
            <div class="ng">ভর্তি ও ফি</div>
            <a class="{{ $nav('admin.students.create') }}" href="{{ route('admin.students.create') }}">@include('partials.icon', ['n' => 'user-plus'])নতুন ভর্তি</a>
            <a class="{{ $nav('admin.enquiries.*') }}" href="{{ route('admin.enquiries.index') }}">@include('partials.icon', ['n' => 'mail'])অনলাইন আবেদন @if ($newEnquiries)<span class="badge b-r" style="margin-left:auto;height:24px;padding:0 9px">{{ bn($newEnquiries) }}</span>@endif</a>
            <a class="{{ $nav('admin.payments.*') }}" href="{{ route('admin.payments.index') }}">@include('partials.icon', ['n' => 'cash'])ফি ও পেমেন্ট</a>
            <a class="{{ $nav('admin.attendance.*') }}" href="{{ route('admin.attendance.index') }}">@include('partials.icon', ['n' => 'check-square'])উপস্থিতি</a>
            <a class="{{ $nav('admin.results.*') }}" href="{{ route('admin.results.index') }}">@include('partials.icon', ['n' => 'file'])ফলাফল</a>
        </div>
        <div class="navgrp">
            <div class="ng">আরও</div>
            <a class="{{ $nav('admin.notices.*') }}" href="{{ route('admin.notices.index') }}">@include('partials.icon', ['n' => 'bell'])নোটিশ</a>
            <a class="{{ $nav('admin.reports.*') }}" href="{{ route('admin.reports.index') }}">@include('partials.icon', ['n' => 'chart'])রিপোর্ট</a>
            @can('admin')
                <a class="{{ $nav('admin.settings.*') }}" href="{{ route('admin.settings.index') }}">@include('partials.icon', ['n' => 'sliders'])সেটিংস</a>
            @endcan
            <a class="nv" href="{{ route('home') }}" target="_blank">@include('partials.icon', ['n' => 'monitor'])ওয়েবসাইট দেখুন</a>
        </div>
    </nav>
    <div style="margin-top:auto;display:flex;align-items:center;gap:12px;padding:12px;border-radius:12px;background:rgba(255,255,255,.08)">
        <span class="av" style="color:#0E3A05">{{ initial($admin->name) }}</span>
        <span style="display:flex;flex-direction:column;line-height:1.35;flex:1;min-width:0"><span style="color:#fff;font-weight:600;font-size:15px">{{ $admin->name }}</span><span style="color:#9DBB93;font-size:13px">{{ $admin->roleLabel() }}</span></span>
        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="el" style="color:#CFE3C8" title="লগআউট" aria-label="লগআউট">@include('partials.icon', ['n' => 'logout'])</button></form>
    </div>
</aside>

<main class="mainc">
    <header class="topbar noprint">
        <nav aria-label="পথ" class="crumbs">
            @yield('crumbs')
        </nav>
        <form method="GET" action="{{ route('admin.students.index') }}" class="search" role="search">
            @include('partials.icon', ['n' => 'search', 'size' => 18, 'color' => '#55635A'])
            <input class="input" type="search" name="q" value="{{ request()->routeIs('admin.students.index') ? request('q') : '' }}" placeholder="স্টুডেন্ট, আইডি বা মোবাইল খুঁজুন…" aria-label="স্টুডেন্ট খুঁজুন">
        </form>
    </header>
    <div class="content">
        @if (session('ok'))
            <div class="flash ok noprint" role="status">{!! session('ok') !!}</div>
        @endif
        @if ($errors->any())
            <div class="flash err noprint" role="alert">ফরমে কিছু সমস্যা আছে, লাল চিহ্নিত ঘরগুলো ঠিক করুন।
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </div>
</main>
</div>
@stack('scripts')
<script src="{{ asset('js/password-toggle.js') }}?v=1" defer></script>
</body>
</html>
