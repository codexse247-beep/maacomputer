@extends('layouts.site')

@section('title', 'গ্যালারি')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/lity/lity.min.css') }}">
<style>
.gal{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr));gap:16px}
.gal figure{margin:0;display:flex;flex-direction:column;gap:6px}
.gal a{position:relative;display:block;border-radius:14px;overflow:hidden;background:#EBEFE8}
.gal img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;transition:transform .25s}
.gal a:hover img,.gal a:focus-visible img{transform:scale(1.04)}
.gal .play{position:absolute;inset:0;margin:auto;width:64px;height:64px;border-radius:50%;background:rgba(176,14,40,.92);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(0,0,0,.3)}
.gal .play svg{fill:#fff;stroke:none;margin-left:4px}
.gal figcaption{font-size:15px;line-height:1.5;color:#14201A}
</style>
@endpush

@section('content')
@include('partials.page-hero', ['eyebrow' => 'গ্যালারি', 'title' => 'ক্লাস ও অনুষ্ঠানের ছবি ও ভিডিও', 'lead' => 'ল্যাব, ক্লাসরুম, সনদ বিতরণ, স্টুডেন্টদের কাজ ও ভিডিও।'])

<section style="padding:56px 0 88px">
<div class="wrap">
    <nav aria-label="গ্যালারির ধরন" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px">
        <a class="tabp {{ ! $category ? 'on' : '' }}" href="{{ route('gallery') }}">সব</a>
        @foreach (\App\Models\GalleryPhoto::CATEGORIES as $key => $label)
            <a class="tabp {{ $category === $key ? 'on' : '' }}" href="{{ route('gallery', ['c' => $key]) }}">{{ $label }}</a>
        @endforeach
        <a class="tabp {{ $category === 'video' ? 'on' : '' }}" href="{{ route('gallery', ['c' => 'video']) }}">ভিডিও</a>
    </nav>
    <div class="gal">
        @forelse ($items as $item)
            @php $name = $item->caption ?: ($item->isVideo() ? 'ভিডিও' : 'গ্যালারির ছবি'); @endphp
            <figure>
                {{-- Lity opens both photos and YouTube videos in a popup on this page. --}}
                <a href="{{ $item->url() }}" data-lity data-lity-desc="{{ $name }}" aria-label="{{ ($item->isVideo() ? 'ভিডিও দেখুন: ' : 'বড় করে দেখুন: ').$name }}">
                    <img src="{{ $item->thumb() }}" alt="{{ $name }}" loading="lazy">
                    @if ($item->isVideo())<span class="play" aria-hidden="true">@include('partials.icon', ['n' => 'play', 'size' => 30])</span>@endif
                </a>
                @if ($item->caption)<figcaption><b>{{ $item->caption }}</b></figcaption>@endif
            </figure>
        @empty
            @for ($i = 1; $i <= 6; $i++)<div class="phbox" style="aspect-ratio:4/3;border-radius:14px">{{ $category === 'video' ? 'ভিডিও' : 'ছবি' }} শীঘ্রই যোগ হবে</div>@endfor
        @endforelse
    </div>
</div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('vendor/lity/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/lity/lity.min.js') }}"></script>
@endpush
