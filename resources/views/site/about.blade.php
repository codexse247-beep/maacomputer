@extends('layouts.site')

@section('title', __('আমাদের সম্পর্কে'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('আমাদের সম্পর্কে'), 'title' => __('দক্ষতা গড়ার প্রতিষ্ঠান'), 'lead' => setting_t('about_intro')])

<section style="padding:56px 0 88px">
<div class="wrap">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr));gap:20px">
        @foreach ([__('আমাদের লক্ষ্য') => setting_t('mission'), __('আমাদের দৃষ্টিভঙ্গি') => setting_t('vision'), __('কেন আমরা') => setting_t('why_us')] as $title => $text)
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
        @foreach ([[bn($departmentCount), __('বিভাগ')], [bn($batchCount), __('চলমান ব্যাচ')], [bn($studentCount), __('সক্রিয় স্টুডেন্ট')], [$certifiedCount ? bn($certifiedCount) : setting('certified_count'), __('সনদপ্রাপ্ত')]] as [$v, $l])
            <div style="text-align:center"><div style="font-size:40px;font-weight:700;color:#176008">{{ $v }}</div><div style="color:#55635A">{{ $l }}</div></div>
        @endforeach
    </div>
</div>
</section>
@endsection
