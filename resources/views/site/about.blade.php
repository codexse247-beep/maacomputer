@extends('layouts.site')

@section('title', 'আমাদের সম্পর্কে')

@section('content')
@include('partials.page-hero', ['eyebrow' => 'আমাদের সম্পর্কে', 'title' => 'দক্ষতা গড়ার প্রতিষ্ঠান', 'lead' => setting('about_intro')])

<section style="padding:56px 0 88px">
<div class="wrap">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr));gap:20px">
        @foreach (['আমাদের লক্ষ্য' => setting('mission'), 'আমাদের দৃষ্টিভঙ্গি' => setting('vision'), 'কেন আমরা' => setting('why_us')] as $title => $text)
        <div class="pcard">
            <h2 style="margin:0 0 8px;font-size:22px;color:#0E3A05">{{ $title }}</h2>
            <p style="margin:0;font-size:16px;line-height:1.75;color:#3F4D44;white-space:pre-line">{{ $text }}</p>
        </div>
        @endforeach
    </div>
</div>
</section>

<section style="padding:48px 0;background:#F1F7EE">
<div class="wrap">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr));gap:20px">
        @foreach ([[bn($departmentCount), 'বিভাগ'], [bn($batchCount), 'চলমান ব্যাচ'], [bn($studentCount), 'সক্রিয় স্টুডেন্ট'], [$certifiedCount ? bn($certifiedCount) : setting('certified_count'), 'সনদপ্রাপ্ত']] as [$v, $l])
            <div style="text-align:center"><div style="font-size:40px;font-weight:700;color:#176008">{{ $v }}</div><div style="color:#55635A">{{ $l }}</div></div>
        @endforeach
    </div>
</div>
</section>
@endsection
