@extends('layouts.site')

@section('title', __('শিক্ষকবৃন্দ'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('শিক্ষকবৃন্দ'), 'title' => __('আমাদের অভিজ্ঞ শিক্ষক'), 'lead' => __('প্রতিটি বিভাগে অভিজ্ঞ প্রশিক্ষক হাতে-কলমে শেখান।')])

<section style="padding:56px 0 88px">
<div class="wrap">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr));gap:20px">
        @forelse ($teachers as $t)
        <div class="pcard" style="display:flex;flex-direction:column;gap:12px">
            @if ($t->photoUrl())
                <img src="{{ $t->photoUrl() }}" alt="{{ $t->tr('name') }}" style="height:220px;width:100%;object-fit:cover;border-radius:12px">
            @else
                <div class="phbox" style="height:180px;font-size:48px">{{ initial($t->tr('name')) }}</div>
            @endif
            <b style="font-size:20px;color:#0E3A05">{{ $t->tr('name') }}</b>
            <span class="badge b-g" style="align-self:flex-start">{{ $t->tr('subject') ?: ($t->department?->tr('name') ?? __('শিক্ষক')) }}</span>
            @if ($t->tr('bio'))<span style="color:#55635A;font-size:15px;white-space:pre-line">{{ $t->tr('bio') }}</span>@endif
        </div>
        @empty
        <p class="mute">{{ __('শিক্ষকদের তথ্য শীঘ্রই যোগ করা হবে।') }}</p>
        @endforelse
    </div>
</div>
</section>
@endsection
