@extends('layouts.site')

@section('title', 'গ্যালারি')

@section('content')
@include('partials.page-hero', ['eyebrow' => 'গ্যালারি', 'title' => 'ক্লাস ও অনুষ্ঠানের ছবি', 'lead' => 'ল্যাব, ক্লাসরুম, সনদ বিতরণ ও স্টুডেন্টদের কাজ।'])

<section style="padding:56px 0 88px">
<div class="wrap">
    <nav aria-label="ছবির ধরন" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px">
        <a class="tabp {{ ! $category ? 'on' : '' }}" href="{{ route('gallery') }}">সব</a>
        @foreach (\App\Models\GalleryPhoto::CATEGORIES as $key => $label)
            <a class="tabp {{ $category === $key ? 'on' : '' }}" href="{{ route('gallery', ['c' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr));gap:16px">
        @forelse ($photos as $p)
            <figure style="margin:0;display:flex;flex-direction:column;gap:6px">
                <a href="{{ $p->url() }}" target="_blank"><img src="{{ $p->url() }}" alt="{{ $p->caption ?: 'গ্যালারির ছবি' }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:14px;display:block"></a>
                @if ($p->caption)<figcaption style="font-size:15px;color:#55635A">{{ $p->caption }}</figcaption>@endif
            </figure>
        @empty
            @for ($i = 1; $i <= 6; $i++)<div class="phbox" style="aspect-ratio:4/3;border-radius:14px">ছবি শীঘ্রই যোগ হবে</div>@endfor
        @endforelse
    </div>
</div>
</section>
@endsection
