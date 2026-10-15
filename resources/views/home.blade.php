@extends('layouts.site')

@section('content')
<section id="home" style="background:#F1F7EE;overflow:hidden">
<div class="wrap" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(480px,100%),1fr));gap:48px;align-items:center;padding-top:72px;padding-bottom:72px">
    <div style="display:flex;flex-direction:column;gap:24px">
        <div class="eyebrow">@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => '#C8102E', 'color' => 'none'])বারইয়ারহাট পৌরসভা · মীরসরাই · চট্টগ্রাম</div>
        <h1 style="margin:0;font-size:clamp(36px,4.8vw,58px);line-height:1.25;font-weight:700;color:#0E3A05;text-wrap:balance">প্রথম দিন থেকেই কাজে লাগানোর মতো <span style="box-shadow:inset 0 -0.25em 0 rgba(200,16,46,.2)">কম্পিউটার দক্ষতা।</span></h1>
        <p style="margin:0;font-size:19px;line-height:1.75;color:#3F4D44;max-width:540px;text-wrap:pretty">অফিস অ্যাপ্লিকেশন, গ্রাফিক ডিজাইন, ওয়েব, ফ্রিল্যান্সিং ও নেটওয়ার্কিংয়ে হাতে-কলমে প্রশিক্ষণ — ছোট ব্যাচে, ভর্তি থেকে সনদ পর্যন্ত স্পষ্ট পথচলা।</p>
        <div style="display:flex;flex-wrap:wrap;gap:12px">
            <a class="btn p" href="{{ route('admission') }}">ভর্তির আবেদন করুন</a>
            <a class="btn" href="{{ route('departments') }}">বিভাগসমূহ দেখুন</a>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:12px 36px;padding-top:14px;border-top:1px solid #CFE0C6;margin-top:8px">
            <div style="display:flex;flex-direction:column"><span style="font-size:30px;font-weight:700;color:#0E3A05;line-height:1.3">{{ bn($departments->count()) }}টি</span><span style="font-size:15px;color:#55635A">বিভাগ</span></div>
            <div style="display:flex;flex-direction:column"><span style="font-size:30px;font-weight:700;color:#0E3A05;line-height:1.3">ছোট ব্যাচ</span><span style="font-size:15px;color:#55635A">বেশি মনোযোগ</span></div>
            <div style="display:flex;flex-direction:column"><span style="font-size:30px;font-weight:700;color:#0E3A05;line-height:1.3">অনলাইন</span><span style="font-size:15px;color:#55635A">স্টুডেন্ট পোর্টাল</span></div>
        </div>
    </div>
    <div class="hero-art" style="position:relative;display:flex;align-items:center;justify-content:center;min-height:460px">
        <div style="position:absolute;width:min(470px,92%);aspect-ratio:1/1;border-radius:50%;background:#DCEBD4"></div>
        <div style="position:absolute;width:min(560px,100%);aspect-ratio:1/1;border-radius:50%;border:2px dashed #B7D3AB"></div>
        <img src="{{ logo_url() }}" alt="মা কম্পিউটার এন্ড ট্রেনিং সেন্টারের প্রতীক" style="position:relative;width:min(390px,80%);aspect-ratio:1/1;border-radius:50%;object-fit:cover;box-shadow:0 24px 60px rgba(14,58,5,.18)">
        <div class="float" style="position:absolute;top:34px;right:0;display:flex;align-items:center;gap:10px;background:#fff;border-radius:14px;padding:12px 16px;box-shadow:0 10px 30px rgba(14,58,5,.14);font-weight:700;font-size:15px;color:#0E3A05">@include('partials.icon', ['n' => 'cert', 'color' => '#176008'])কোর্স শেষে সনদ</div>
        <div class="float" style="position:absolute;bottom:40px;left:0;display:flex;align-items:center;gap:10px;background:#fff;border-radius:14px;padding:12px 16px;box-shadow:0 10px 30px rgba(14,58,5,.14);font-weight:700;font-size:15px;color:#0E3A05">@include('partials.icon', ['n' => 'monitor', 'color' => '#C8102E'])হাতে-কলমে ল্যাব প্র্যাকটিস</div>
    </div>
</div>
</section>

<section id="departments" style="padding:96px 0">
<div class="wrap">
    <div class="eyebrow">@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => '#C8102E', 'color' => 'none'])বিভাগসমূহ</div>
    <h2 class="h2">{{ bn($departments->count()) }}টি বিভাগ, একটি স্পষ্ট পথ</h2>
    <p class="sub">একটি বিভাগ বেছে নিন, ব্যাচে যোগ দিন এবং নিজের স্তরের সহপাঠীদের সাথে শিখুন। প্রতিটি ব্যাচের আছে নির্দিষ্ট রুটিন, শিক্ষক ও অগ্রগতির রেকর্ড।</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(340px,100%),1fr));gap:24px;margin-top:48px">
        @foreach ($departments as $d)
        <a class="dcard" href="{{ route('departments') }}#{{ strtolower($d->code) }}">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span class="code" style="width:52px;height:52px;border-radius:14px;font-size:18px">{{ $d->code }}</span>
                @if ($d->duration)<span style="font-size:14px;font-weight:600;color:#55635A">মেয়াদ · {{ $d->duration }}</span>@endif
            </div>
            <h3 style="margin:6px 0 0;font-size:22px;line-height:1.4;font-weight:700;color:#0E3A05">{{ $d->name }}</h3>
            @if ($d->blurb)<p style="margin:0;font-size:16px;line-height:1.7;color:#55635A">{{ $d->blurb }}</p>@endif
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:auto;padding-top:6px">
                @foreach ($d->topicList() as $t)<span class="chip">{{ $t }}</span>@endforeach
            </div>
        </a>
        @endforeach
    </div>
</div>
</section>

<section id="admission" style="background:#F5F7F3;padding:96px 0">
<div class="wrap">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:24px">
        <div>
            <div class="eyebrow">@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => '#C8102E', 'color' => 'none'])ভর্তি</div>
            <h2 class="h2">প্রথম পরিদর্শন থেকে সনদ পর্যন্ত</h2>
        </div>
        <a class="btn p" href="{{ route('contact') }}">ভর্তি শুরু করুন</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(250px,100%),1fr));gap:24px;margin-top:48px">
        <div class="pstep"><span class="n">১</span><h3>বিভাগ বেছে নিন</h3><p>লক্ষ্য অনুযায়ী কোর্স বাছুন। সঠিক স্তরে ভর্তি হতে আমাদের স্টাফ সাহায্য করবেন।</p></div>
        <div class="pstep"><span class="n">২</span><h3>ভর্তি ফরম জমা দিন</h3><p>ব্যক্তিগত, অভিভাবক ও শিক্ষাগত তথ্য পূরণ করুন এবং ছবি ও পরিচয়পত্রের কপি আনুন।</p></div>
        <div class="pstep"><span class="n">৩</span><h3>ব্যাচ ও স্টুডেন্ট আইডি পান</h3><p>নির্দিষ্ট রুটিনসহ একটি ব্যাচে যুক্ত হবেন এবং স্টুডেন্ট আইডি ও পোর্টাল লগইন পাবেন।</p></div>
        <div class="pstep"><span class="n">৪</span><h3>শিখুন ও অগ্রগতি দেখুন</h3><p>ক্লাস করুন, মূল্যায়নে অংশ নিন এবং পোর্টালে উপস্থিতি, ফি ও ফলাফল দেখুন।</p></div>
    </div>
</div>
</section>

<section id="portal" style="background:#0E3A05;color:#fff;padding:96px 0;overflow:hidden">
<div class="wrap" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(460px,100%),1fr));gap:56px;align-items:center">
    <div style="display:flex;flex-direction:column;gap:22px">
        <div class="eyebrow" style="color:#F5A3AE">@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => '#F5A3AE', 'color' => 'none'])স্টুডেন্ট পোর্টাল</div>
        <h2 style="margin:0;font-size:clamp(28px,3.4vw,40px);line-height:1.3;font-weight:700;text-wrap:balance">আপনার ব্যাচ, উপস্থিতি ও ফি — সব এক জায়গায়।</h2>
        <p style="margin:0;font-size:18px;line-height:1.75;color:#CFE3C8;max-width:520px">স্টুডেন্ট আইডি দিয়ে লগইন করে ক্লাস রুটিন, উপস্থিতি, পেমেন্টের ইতিহাস, ফলাফল ও নোটিশ দেখুন — যেকোনো সময় আইডি কার্ড বা রসিদ ডাউনলোড করুন।</p>
        <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:12px;font-weight:600;font-size:17px">
            <li style="display:flex;gap:12px;align-items:center">@include('partials.icon', ['n' => 'check', 'color' => '#8FD17C', 'w' => 2.4])ক্লাস রুটিন ও উপস্থিতির রেকর্ড</li>
            <li style="display:flex;gap:12px;align-items:center">@include('partials.icon', ['n' => 'check', 'color' => '#8FD17C', 'w' => 2.4])ফি পরিশোধ, বকেয়ার তারিখ ও রসিদ</li>
            <li style="display:flex;gap:12px;align-items:center">@include('partials.icon', ['n' => 'check', 'color' => '#8FD17C', 'w' => 2.4])মূল্যায়নের ফলাফল ও সনদ</li>
        </ul>
        <div><a class="btn w" href="{{ route('login') }}">স্টুডেন্ট পোর্টাল খুলুন</a></div>
    </div>
    <div style="display:flex;justify-content:center">
        <div style="width:100%;max-width:460px;background:#fff;color:#14201A;border-radius:22px;padding:24px;box-shadow:0 30px 70px rgba(0,0,0,.35);display:flex;flex-direction:column;gap:18px" aria-label="পোর্টালের নমুনা">
            <div style="display:flex;align-items:center;gap:14px">
                <span class="av" style="width:52px;height:52px;font-size:20px">স্টু</span>
                <div style="display:flex;flex-direction:column"><span style="font-weight:700;font-size:18px;color:#0E3A05">আপনার নাম</span><span style="font-size:14px;color:#55635A">বিভাগ · ব্যাচ · স্টুডেন্ট আইডি</span></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div style="background:#F1F7EE;border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:6px"><span style="font-size:13px;font-weight:700;color:#55635A">উপস্থিতি</span><span style="font-size:30px;font-weight:700;color:#176008;line-height:1.2">৯২%</span><span class="bar thin"><span style="width:92%"></span></span></div>
                <div style="background:#FCEBEC;border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:6px"><span style="font-size:13px;font-weight:700;color:#55635A">ফি বকেয়া</span><span style="font-size:30px;font-weight:700;color:#B00E28;line-height:1.2">৳৪,০০০</span><span style="font-size:13px;color:#55635A">পরবর্তী কিস্তির তারিখসহ</span></div>
            </div>
            <div style="border:1px solid #DFE5DB;border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:4px"><span style="font-size:13px;font-weight:700;color:#55635A">ক্লাস রুটিন</span><span style="font-weight:700;color:#0E3A05">সপ্তাহের দিন, সময় ও শিক্ষক</span><span style="font-size:14px;color:#55635A">যেমন: শনি · সোম · বুধ, সকাল ১০:০০</span></div>
        </div>
    </div>
</div>
</section>

<section id="notices" style="padding:96px 0">
<div class="wrap">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:24px">
        <div>
            <div class="eyebrow">@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => '#C8102E', 'color' => 'none'])নোটিশ বোর্ড</div>
            <h2 class="h2">সর্বশেষ নোটিশ</h2>
        </div>
        <a class="btn" href="{{ route('notices') }}">সব নোটিশ</a>
    </div>
    @include('partials.notice-list', ['notices' => $notices])
</div>
</section>

<section id="contact" style="background:#176008;color:#fff;padding:80px 0">
<div class="wrap" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(300px,100%),1fr));gap:40px 56px;align-items:center">
    <div style="display:flex;flex-direction:column;gap:18px">
        <h2 style="margin:0;font-size:clamp(28px,3.4vw,40px);line-height:1.3;font-weight:700;text-wrap:balance">কেন্দ্রে আসুন, শেখা শুরু করুন।</h2>
        <p style="margin:0;font-size:18px;line-height:1.75;color:#DCEBD4;max-width:480px">ল্যাব দেখুন, শিক্ষকদের সাথে কথা বলুন এবং এক সফরেই ভর্তি সম্পন্ন করুন।</p>
        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px">
            <a class="btn w" href="{{ route('admission') }}">ভর্তির আবেদন করুন</a>
            <a class="btn o" href="{{ route('login') }}">স্টুডেন্ট লগইন</a>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:20px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);border-radius:18px;padding:28px">
        <div style="display:flex;gap:14px;align-items:flex-start">@include('partials.icon', ['n' => 'pin', 'size' => 22, 'style' => 'flex:none;margin-top:4px'])<span style="font-size:17px;line-height:1.6">{{ setting('area') }}<br>{{ setting('address') }}</span></div>
        <div style="display:flex;gap:14px;align-items:center">@include('partials.icon', ['n' => 'phone', 'size' => 22, 'style' => 'flex:none'])<span style="font-size:17px">{{ setting('phone') }}</span></div>
        <div style="display:flex;gap:14px;align-items:center">@include('partials.icon', ['n' => 'mail', 'size' => 22, 'style' => 'flex:none'])<span style="font-size:17px">{{ setting('email') }}</span></div>
        <div style="display:flex;gap:14px;align-items:center">@include('partials.icon', ['n' => 'clock', 'size' => 22, 'style' => 'flex:none'])<span style="font-size:17px">{{ setting('hours') }}</span></div>
    </div>
</div>
</section>
@endsection
