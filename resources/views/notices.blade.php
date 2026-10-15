@extends('layouts.site')

@section('title', 'নোটিশ বোর্ড')

@section('content')
@include('partials.page-hero', ['eyebrow' => 'নোটিশ বোর্ড', 'title' => 'সব নোটিশ এক জায়গায়', 'lead' => 'ভর্তি, পরীক্ষা, ফলাফল ও ছুটির সর্বশেষ ঘোষণা এখানে পাবেন।'])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;flex-direction:column;gap:28px">
    <nav aria-label="নোটিশের ধরন" style="display:flex;flex-wrap:wrap;gap:10px">
        <a class="tabp {{ ! $tag ? 'on' : '' }}" href="{{ route('notices') }}">সব</a>
        @foreach (['admission', 'exam', 'result', 'holiday', 'fee'] as $key)
            <a class="tabp {{ $tag === $key ? 'on' : '' }}" href="{{ route('notices', ['tag' => $key]) }}">{{ \App\Models\Notice::TAGS[$key][0] }}</a>
        @endforeach
    </nav>
    <div style="display:flex;flex-direction:column;border:1px solid #DFE5DB;border-bottom:0;border-radius:18px;overflow:hidden">
        @forelse ($notices as $n)
            <div class="prow">
                <span style="min-width:96px;padding:8px 12px;border-radius:10px;background:#E3F0DD;color:#176008;font-weight:700;font-size:15px;text-align:center">{{ bn_date($n->published_on, false) }}</span>
                <span style="flex:1 1 300px;display:flex;flex-direction:column;line-height:1.5"><b style="font-size:18px;color:#0E3A05">{{ $n->title }}</b>@if ($n->body)<span style="font-size:15px;color:#55635A;white-space:pre-line">{{ $n->body }}</span>@endif</span>
                <span class="badge {{ $n->tagTone() }}" style="height:30px;font-size:14px">{{ $n->tagLabel() }}</span>
            </div>
        @empty
            <div class="prow mute">এই ধরনের কোনো নোটিশ নেই।</div>
        @endforelse
    </div>
    @if ($notices->hasMorePages())
        <div style="display:flex;justify-content:center"><a class="btn" href="{{ $notices->nextPageUrl() }}">আরও দেখুন</a></div>
    @endif
</div>
</section>
@endsection
