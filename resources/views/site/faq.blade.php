@extends('layouts.site')

@section('title', __('প্রশ্নোত্তর'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('সাধারণ প্রশ্ন'), 'title' => __('প্রায়ই জিজ্ঞাসিত প্রশ্ন'), 'lead' => __('ভর্তির আগে সাধারণত যা জানতে চাওয়া হয়।')])

<section style="padding:56px 0 88px">
<div class="wrap">
    <div style="max-width:820px;margin:0 auto;display:flex;flex-direction:column;gap:12px">
        @foreach ($faqs as $q)
        <details style="border:1px solid #DFE5DB;border-radius:14px;background:#fff;padding:0 22px" @if ($loop->first) open @endif>
            <summary style="min-height:56px;display:flex;align-items:center;cursor:pointer;font-weight:700;font-size:17px;color:#0E3A05">{{ $q->tr('question') }}</summary>
            <p style="margin:0 0 18px;font-size:16px;line-height:1.75;color:#3F4D44;white-space:pre-line">{{ $q->tr('answer') }}</p>
        </details>
        @endforeach
        <p class="mute" style="margin-top:12px">{{ __('আরও প্রশ্ন থাকলে') }} <a href="{{ route('contact') }}">{{ __('আমাদের সাথে যোগাযোগ করুন') }}</a>{{ __('।') }}</p>
    </div>
</div>
</section>
@endsection
