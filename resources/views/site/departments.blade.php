@extends('layouts.site')

@section('title', __('বিভাগ ও কোর্স'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('বিভাগ ও কোর্স'), 'title' => __(':countটি বিভাগে আপনার পছন্দের কোর্স', ['count' => bn($departments->count())]), 'lead' => __('প্রতিটি বিভাগে আছে নির্দিষ্ট মেয়াদ, ক্লাস রুটিন ও ছোট ব্যাচ। ভর্তির আগে কোর্সের বিষয়বস্তু দেখে নিন।')])

<section style="padding:64px 0 88px">
<div class="wrap" style="display:flex;flex-direction:column;gap:24px">
    @foreach ($departments as $d)
    <article id="{{ strtolower($d->code) }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr));gap:28px 40px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:20px;background:#fff">
        <div style="display:flex;flex-direction:column;gap:14px">
            <div style="display:flex;align-items:center;gap:16px">
                <span class="code" style="width:56px;height:56px;border-radius:14px;font-size:20px">{{ $d->code }}</span>
                <h2 style="margin:0;font-size:clamp(20px,2.4vw,25px);line-height:1.35;font-weight:700;color:#0E3A05">{{ $d->tr('name') }}</h2>
            </div>
            @if ($d->tr('blurb'))<p style="margin:0;font-size:17px;line-height:1.75;color:#55635A">{{ $d->tr('blurb') }}</p>@endif
            <div style="display:flex;flex-wrap:wrap;gap:8px">@foreach ($d->topicList() as $t)<span class="chip">{{ $t }}</span>@endforeach</div>
            <div style="margin-top:6px"><a class="btn p" href="{{ route('admission', ['department' => $d->id]) }}">{{ __('এই বিভাগে ভর্তি হোন') }}</a></div>
        </div>
        <div style="display:flex;flex-direction:column;gap:20px">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:16px">
                <div class="fact"><span>{{ __('মেয়াদ') }}</span><b>{{ $d->tr('duration') ?: '—' }}</b></div>
                <div class="fact"><span>{{ __('ক্লাস রুটিন') }}</span><b>{{ $d->tr('routine_note') ?: __('সপ্তাহে ৩ দিন') }}</b></div>
                <div class="fact"><span>{{ __('কোর্স ফি') }}</span><b>{{ $d->course_fee ? taka($d->course_fee) : __('অফিসে জানুন') }}</b></div>
            </div>
            @if ($d->learnList())
            <div>
                <div style="font-size:14px;font-weight:600;color:#55635A;margin-bottom:8px">{{ __('কী শিখবেন') }}</div>
                <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:8px">
                    @foreach ($d->learnList() as $l)
                        <li style="display:flex;gap:10px;align-items:flex-start;font-size:16px">@include('partials.icon', ['n' => 'check', 'color' => '#176008', 'w' => 2.4, 'style' => 'flex:none;margin-top:3px'])<span>{{ $l }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </article>
    @endforeach
</div>
</section>
@endsection
