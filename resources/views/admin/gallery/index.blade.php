@extends('layouts.admin')

@section('title', 'গ্যালারি')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>গ্যালারি</strong>@endsection

@php $kind = old('type', 'image'); @endphp

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/lity/lity.min.css') }}">
@endpush

@section('content')
<div>
    <h1 class="h1">গ্যালারি</h1>
    <p class="lead">ওয়েবসাইটের গ্যালারিতে ছবি ও ভিডিও যোগ করুন</p>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" class="card" style="flex:1 1 340px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:16px">
        @csrf
        <h2 style="margin:0;font-size:20px;color:#0E3A05">নতুন যোগ করুন</h2>
        @include('admin.partials.select', ['name' => 'type', 'label' => 'টাইপ', 'req' => true, 'value' => $kind, 'options' => \App\Models\GalleryPhoto::TYPES, 'id' => 'g_type'])

        <div data-kind="image" @if ($kind !== 'image') hidden @endif>
            @include('admin.partials.upload', ['name' => 'photos[]', 'label' => 'ছবি আপলোড (একসাথে কয়েকটি বাছতে পারেন)', 'btn' => 'ছবি বাছুন', 'accept' => 'image/*', 'multiple' => true, 'id' => 'g_photos'])
        </div>
        <div data-kind="video" @if ($kind !== 'video') hidden @endif>
            @include('admin.partials.field', ['name' => 'video_url', 'label' => 'ভিডিও লিংক (ইউটিউব)', 'value' => '', 'ph' => 'https://www.youtube.com/watch?v=…', 'hint' => 'ইউটিউবে ভিডিওর নিচে "Share" চেপে লিংক কপি করে এখানে বসান।', 'id' => 'g_url'])
        </div>

        @include('admin.partials.field', ['name' => 'caption', 'label' => 'শিরোনাম (ঐচ্ছিক)', 'value' => '', 'id' => 'g_cap'])
        @include('admin.partials.select', ['name' => 'category', 'label' => 'ধরন', 'value' => 'lab', 'options' => \App\Models\GalleryPhoto::CATEGORIES, 'id' => 'g_cat'])
        <div><button class="btn p">যোগ করুন</button></div>
    </form>

    <div class="card" style="flex:2 1 440px;min-width:0;overflow:hidden">
        <div style="padding:18px 24px;border-bottom:1px solid #DFE5DB"><b style="font-size:18px;color:#0E3A05">গ্যালারির ছবি ও ভিডিও</b></div>
        <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px">
            @forelse ($items as $it)
                <figure style="margin:0;display:flex;flex-direction:column;gap:4px">
                    <a href="{{ $it->url() }}" data-lity style="position:relative;display:block" aria-label="দেখুন">
                        <img src="{{ $it->thumb() }}" alt="{{ $it->caption }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:10px;display:block">
                        @if ($it->isVideo())<span class="badge b-r" style="position:absolute;left:8px;top:8px;height:24px">▶ ভিডিও</span>@endif
                    </a>
                    <figcaption class="mute" style="font-size:13px">{{ \App\Models\GalleryPhoto::CATEGORIES[$it->category] ?? '' }}{{ $it->caption ? ' · '.$it->caption : '' }}</figcaption>
                    <form method="POST" action="{{ route('admin.gallery.destroy', $it) }}" onsubmit="return confirm('মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28;height:32px;padding:0">মুছুন</button></form>
                </figure>
            @empty
                <div class="mute">এখনো কিছু যোগ করা হয়নি।</div>
            @endforelse
        </div>
        <div class="pager" style="display:block">{{ $items->links('partials.pager') }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('g_type').addEventListener('change', function () {
    document.querySelectorAll('[data-kind]').forEach((el) => { el.hidden = el.dataset.kind !== this.value; });
});
document.getElementById('g_photos').addEventListener('change', function () {
    const label = document.querySelector('label[for="g_photos"]');
    label.textContent = this.files.length > 1 ? this.files.length + 'টি ছবি বাছাই হয়েছে' : (this.files[0] ? this.files[0].name : label.dataset.text);
});
</script>
<script src="{{ asset('vendor/lity/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/lity/lity.min.js') }}"></script>
@endpush
