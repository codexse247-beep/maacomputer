@extends('layouts.site')

@section('title', 'শিক্ষকবৃন্দ')

@section('content')
@include('partials.page-hero', ['eyebrow' => 'শিক্ষকবৃন্দ', 'title' => 'আমাদের অভিজ্ঞ শিক্ষক', 'lead' => 'প্রতিটি বিভাগে অভিজ্ঞ প্রশিক্ষক হাতে-কলমে শেখান।'])

<section style="padding:56px 0 88px">
<div class="wrap">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr));gap:20px">
        @forelse ($teachers as $t)
        <div class="pcard" style="display:flex;flex-direction:column;gap:12px">
            @if ($t->photoUrl())
                <img src="{{ $t->photoUrl() }}" alt="{{ $t->name }}" style="height:220px;width:100%;object-fit:cover;border-radius:12px">
            @else
                <div class="phbox" style="height:180px;font-size:48px">{{ initial($t->name) }}</div>
            @endif
            <b style="font-size:20px;color:#0E3A05">{{ $t->name }}</b>
            <span class="badge b-g" style="align-self:flex-start">{{ $t->subject ?: ($t->department?->name ?? 'শিক্ষক') }}</span>
            @if ($t->bio)<span style="color:#55635A;font-size:15px;white-space:pre-line">{{ $t->bio }}</span>@endif
        </div>
        @empty
        <p class="mute">শিক্ষকদের তথ্য শীঘ্রই যোগ করা হবে।</p>
        @endforelse
    </div>
</div>
</section>
@endsection
