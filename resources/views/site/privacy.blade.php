@extends('layouts.site')

@section('title', __('গোপনীয়তা ও শর্ত'))

@php
    // "## Heading" lines start a section; everything else is paragraph text.
    $sections = [];
    foreach (preg_split('/\r?\n/', setting_t('privacy')) as $line) {
        if (str_starts_with(trim($line), '## ')) {
            $sections[] = ['h' => trim(substr(trim($line), 3)), 'p' => ''];
        } elseif ($sections) {
            $sections[count($sections) - 1]['p'] .= $line."\n";
        } elseif (trim($line) !== '') {
            $sections[] = ['h' => '', 'p' => $line."\n"];
        }
    }
@endphp

@section('content')
@include('partials.page-hero', ['eyebrow' => __('আইনি'), 'title' => __('গোপনীয়তা নীতি ও শর্তাবলি'), 'lead' => __('কার্যকর: :date', ['date' => setting_t('privacy_date')])])

<section style="padding:56px 0 88px">
<div class="wrap">
    <div style="max-width:820px;margin:0 auto;display:flex;flex-direction:column;gap:28px">
        @foreach ($sections as $s)
        <div>
            @if ($s['h'])<h2 style="margin:0 0 8px;font-size:22px;color:#0E3A05">{{ $s['h'] }}</h2>@endif
            <p style="margin:0;font-size:16px;line-height:1.8;color:#3F4D44;white-space:pre-line">{{ trim($s['p']) }}</p>
        </div>
        @endforeach
    </div>
</div>
</section>
@endsection
