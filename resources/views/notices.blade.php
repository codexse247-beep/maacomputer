@extends('layouts.site')

@section('title', __('নোটিশ বোর্ড'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('নোটিশ বোর্ড'), 'title' => __('সব নোটিশ এক জায়গায়'), 'lead' => __('ভর্তি, পরীক্ষা, ফলাফল ও ছুটির সর্বশেষ ঘোষণা এখানে পাবেন।')])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;flex-direction:column;gap:28px">
    @if ($events->isNotEmpty())
        <div style="display:flex;flex-direction:column;gap:10px;padding:20px 24px;border:1px solid #DFE5DB;border-radius:18px;background:#F8FBF6">
            <b style="font-size:18px;color:#0E3A05">{{ __('আসন্ন ছুটি ও অনুষ্ঠান') }}</b>
            @foreach ($events as $e)
                <div style="display:flex;flex-wrap:wrap;gap:6px 12px;align-items:center"><span class="badge {{ $e->typeTone() }}">{{ __($e->typeLabel()) }}</span><b>{{ $e->title }}</b><span class="mute">{{ $e->dateLabel() }}</span></div>
            @endforeach
        </div>
    @endif
    <nav aria-label="{{ __('নোটিশের ধরন') }}" style="display:flex;flex-wrap:wrap;gap:10px">
        <a class="tabp {{ ! $tag ? 'on' : '' }}" href="{{ route('notices') }}">{{ __('সব') }}</a>
        @foreach (['admission', 'exam', 'result', 'holiday', 'fee'] as $key)
            <a class="tabp {{ $tag === $key ? 'on' : '' }}" href="{{ route('notices', ['tag' => $key]) }}">{{ __(\App\Models\Notice::TAGS[$key][0]) }}</a>
        @endforeach
    </nav>
    <div style="display:flex;flex-direction:column;border:1px solid #DFE5DB;border-bottom:0;border-radius:18px;overflow:hidden">
        @forelse ($notices as $n)
            <div class="prow">
                <span style="min-width:96px;padding:8px 12px;border-radius:10px;background:#E3F0DD;color:#176008;font-weight:700;font-size:15px;text-align:center">{{ bn_date($n->published_on, false) }}</span>
                <span style="flex:1 1 300px;display:flex;flex-direction:column;line-height:1.5"><b style="font-size:18px;color:#0E3A05">{{ $n->title }}</b>@if ($n->body)<span style="font-size:15px;color:#55635A;white-space:pre-line">{{ $n->body }}</span>@endif</span>
                <span class="badge {{ $n->tagTone() }}" style="height:30px;font-size:14px">{{ __($n->tagLabel()) }}</span>
            </div>
        @empty
            <div class="prow mute">{{ __('এই ধরনের কোনো নোটিশ নেই।') }}</div>
        @endforelse
    </div>
    @if ($notices->hasMorePages())
        <div style="display:flex;justify-content:center"><a class="btn" href="{{ $notices->nextPageUrl() }}">{{ __('আরও দেখুন') }}</a></div>
    @endif
</div>
</section>
@endsection
