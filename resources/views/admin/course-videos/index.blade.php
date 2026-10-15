@extends('layouts.admin')

@section('title', 'কোর্স ভিডিও')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>কোর্স ভিডিও</strong>@endsection

@php
    $v = $video ?? new \App\Models\CourseVideo(['type' => 'upload', 'department_id' => $course?->id, 'class_no' => ($videos->max('class_no') ?? 0) + 1]);
    $kind = old('type', $v->type);
    $dep = (int) old('department_id', $v->department_id);
@endphp

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/lity/lity.min.css') }}">
<style>
.lity-hide{display:none}.vbox{width:min(960px,92vw);background:#000}.vbox video{width:100%;max-height:80vh;display:block}
.cls{display:flex;flex-wrap:wrap;align-items:flex-start;gap:10px 16px;padding:16px 24px;border-bottom:1px solid #DFE5DB}
.cls.on{background:#F1F7EE}
.cls details{flex:1 1 100%}
.cls summary{cursor:pointer;font-size:14px;font-weight:600;color:#176008}
.who{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:4px 16px;padding:10px 0 0;font-size:14px}
.chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;background:#EBEFE8;border-radius:8px;padding:4px 10px;text-decoration:none;color:#14201A}
</style>
@endpush

@section('content')
<div>
    <h1 class="h1">কোর্স ভিডিও</h1>
    <p class="lead">প্রতিটি ক্লাসের ভিডিও ও নোট — শুধু ওই কোর্সে ভর্তি স্টুডেন্টরা পোর্টালে লগইন করে দেখতে পাবে</p>
</div>

@if ($departments->isEmpty())
    <div class="card" style="padding:24px">আগে একটি বিভাগ (কোর্স) তৈরি করুন।</div>
@else
<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <form method="POST" action="{{ $v->exists ? route('admin.course-videos.update', $v) : route('admin.course-videos.store') }}" enctype="multipart/form-data" class="card" id="cv_form" style="flex:1 1 360px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:16px">
        @csrf
        @if ($v->exists) @method('PUT') @endif
        <h2 style="margin:0;font-size:20px;color:#0E3A05">{{ $v->exists ? 'ক্লাস সম্পাদনা' : 'নতুন ক্লাস' }}</h2>

        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div style="flex:1 1 160px">@include('admin.partials.select', ['name' => 'department_id', 'label' => 'কোর্স', 'req' => true, 'value' => $v->department_id, 'options' => $departments->pluck('name', 'id')->all(), 'id' => 'cv_dep'])</div>
            <div class="ff" style="flex:1 1 160px">
                <label for="cv_batch">কোন ব্যাচ দেখবে</label>
                <select class="in" id="cv_batch" name="batch_id">
                    <option value="">কোর্সের সব ব্যাচ</option>
                    @foreach ($batches as $b)
                        <option value="{{ $b->id }}" data-dep="{{ $b->department_id }}" @selected((int) old('batch_id', $v->batch_id) === $b->id) @if ($b->department_id !== $dep) hidden disabled @endif>{{ $b->label() }}</option>
                    @endforeach
                </select>
                @error('batch_id')<span class="errm">{{ $message }}</span>@enderror
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div style="flex:1 1 90px">@include('admin.partials.field', ['name' => 'class_no', 'label' => 'ক্লাস নং', 'value' => $v->class_no, 'mode' => 'numeric', 'hint' => 'এই নম্বরে সাজানো হবে', 'id' => 'cv_no'])</div>
            <div style="flex:3 1 200px">@include('admin.partials.field', ['name' => 'title', 'label' => 'শিরোনাম', 'req' => true, 'value' => $v->title, 'ph' => 'যেমন: ফটোশপ পরিচিতি', 'id' => 'cv_title'])</div>
        </div>
        <div class="ff">
            <label for="cv_desc">বিবরণ (ঐচ্ছিক)</label>
            <textarea class="ta" id="cv_desc" name="description" placeholder="এই ক্লাসে কী শেখানো হয়েছে, বাড়ির কাজ ইত্যাদি" style="min-height:80px">{{ old('description', $v->description) }}</textarea>
            @error('description')<span class="errm">{{ $message }}</span>@enderror
        </div>

        @include('admin.partials.select', ['name' => 'type', 'label' => 'ভিডিওর টাইপ', 'req' => true, 'value' => $kind, 'options' => \App\Models\CourseVideo::TYPES, 'id' => 'cv_type'])
        <div data-kind="upload" @if ($kind !== 'upload') hidden @endif>
            @include('admin.partials.upload', ['name' => 'video', 'label' => $v->exists && $v->path ? 'ভিডিও বদলান (না দিলে আগেরটাই থাকবে)' : 'ভিডিও ফাইল (MP4, সর্বোচ্চ ৫০০ MB)', 'btn' => 'ভিডিও বাছুন', 'accept' => 'video/mp4,video/webm', 'id' => 'cv_file'])
            <small class="mute" style="display:block;margin-top:6px">১০০ MB-এর বড় ভিডিও এই কম্পিউটার থেকেই আপলোড করুন — বাইরে থেকে বড় ফাইল আটকে যায়।</small>
        </div>
        <div data-kind="url" @if ($kind !== 'url') hidden @endif>
            @include('admin.partials.field', ['name' => 'url', 'label' => 'ভিডিও লিংক', 'value' => $v->url, 'ph' => 'https://youtu.be/…', 'hint' => 'ইউটিউব (Unlisted রাখলে বাইরের কেউ খুঁজে পাবে না), গুগল ড্রাইভ বা ভিমিও লিংক।', 'id' => 'cv_url'])
        </div>

        <div style="display:flex;flex-direction:column;gap:8px">
            @include('admin.partials.upload', ['name' => 'files[]', 'label' => 'নোট / প্র্যাকটিস ফাইল (ঐচ্ছিক — PDF, Word, Excel, ZIP, PSD…)', 'btn' => 'ফাইল বাছুন', 'accept' => '.'.str_replace(',', ',.', \App\Models\CourseFile::EXTENSIONS), 'multiple' => true, 'id' => 'cv_files'])
            @if ($v->exists && $v->files->isNotEmpty())
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @foreach ($v->files as $f)
                        <span class="chip">{{ $f->name }} <button class="el" form="delf{{ $f->id }}" style="height:auto;padding:0;color:#B00E28" aria-label="{{ $f->name }} মুছুন" title="মুছুন">✕</button></span>
                    @endforeach
                </div>
            @endif
        </div>

        <fieldset style="border:1px solid #DFE5DB;border-radius:12px;margin:0;padding:12px 16px;display:flex;flex-direction:column;gap:8px">
            <legend class="lbl" style="padding:0 6px">লক (ঐচ্ছিক)</legend>
            @include('admin.partials.field', ['name' => 'release_on', 'label' => 'যে তারিখ থেকে দেখা যাবে', 'type' => 'date', 'value' => $v->release_on, 'id' => 'cv_rel'])
            <label class="sw"><input type="checkbox" name="paid_only" value="1" @checked($errors->any() ? old('paid_only') : $v->paid_only)>শুধু যাদের কোনো ফি বকেয়া নেই তারা দেখবে</label>
        </fieldset>

        <div class="row">
            @if ($v->exists)<a class="btn" href="{{ route('admin.course-videos.index', ['course' => $v->department_id]) }}">বাতিল</a>@endif
            <button class="btn p" id="cv_btn">{{ $v->exists ? 'সংরক্ষণ' : 'যোগ করুন' }}</button>
        </div>
    </form>
    @if ($v->exists)
        @foreach ($v->files as $f)
            <form id="delf{{ $f->id }}" method="POST" action="{{ route('admin.course-files.destroy', $f) }}" onsubmit="return confirm('ফাইলটি মুছবেন?')" hidden>@csrf @method('DELETE')</form>
        @endforeach
    @endif

    <div class="card" style="flex:2 1 460px;min-width:0;overflow:hidden">
        <div style="padding:18px 24px;border-bottom:1px solid #DFE5DB;display:flex;flex-wrap:wrap;gap:8px">
            @foreach ($departments as $d)
                <a class="btn sm {{ $course->id === $d->id ? 'p' : '' }}" href="{{ route('admin.course-videos.index', ['course' => $d->id]) }}">{{ $d->name }} ({{ bn($counts[$d->id] ?? 0) }})</a>
            @endforeach
        </div>
        @forelse ($videos as $item)
            @php
                $eligible = $students->filter(fn ($s) => ! $item->batch_id || $s->batch_id === $item->batch_id);
                $seen = $views[$item->id] ?? collect();
                $seenCount = $eligible->filter(fn ($s) => $seen->has($s->id))->count();
            @endphp
            <div class="cls {{ $v->exists && $v->id === $item->id ? 'on' : '' }}">
                <span class="badge b-g" style="min-width:76px;justify-content:center">{{ $item->class_no ? 'ক্লাস '.bn($item->class_no) : '—' }}</span>
                <span style="flex:1 1 220px;min-width:0;display:flex;flex-direction:column;gap:2px;line-height:1.5">
                    <b>{{ $item->title }}</b>
                    <span class="mute" style="font-size:13px">
                        {{ $item->isUpload() ? 'আপলোড করা ভিডিও' : parse_url($item->url, PHP_URL_HOST) }}{{ $item->files->count() ? ' · '.bn($item->files->count()).'টি ফাইল' : '' }}
                    </span>
                    @if ($item->rulesLabel())<span style="font-size:13px;color:#8A5300;font-weight:600">🔒 {{ $item->rulesLabel() }}</span>@endif
                </span>
                <a class="btn sm" href="{{ $item->isUpload() ? '#v'.$item->id : $item->playUrl() }}" data-lity>@include('partials.icon', ['n' => 'play', 'size' => 16])দেখুন</a>
                <a class="btn sm" href="{{ route('admin.course-videos.index', ['edit' => $item->id]) }}">সম্পাদনা</a>
                <form method="POST" action="{{ route('admin.course-videos.destroy', $item) }}" onsubmit="return confirm('ক্লাসটি (ভিডিও ও ফাইলসহ) মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>

                @if ($item->files->isNotEmpty())
                    <div style="flex:1 1 100%;display:flex;flex-wrap:wrap;gap:8px">
                        @foreach ($item->files as $f)<a class="chip" href="{{ route('admin.course-files.download', $f) }}">@include('partials.icon', ['n' => 'download', 'size' => 14]){{ $f->name }}</a>@endforeach
                    </div>
                @endif
                <details>
                    <summary>দেখেছে {{ bn($seenCount) }}/{{ bn($eligible->count()) }} জন</summary>
                    <div class="who">
                        @forelse ($eligible as $s)
                            <span style="{{ $seen->has($s->id) ? 'color:#176008' : 'color:#8B958E' }}">{{ $seen->has($s->id) ? '✓' : '✗' }} {{ $s->name_bn }} <small class="mute">({{ $s->batch->code }})</small></span>
                        @empty
                            <span class="mute">এই ক্লাসের জন্য কোনো স্টুডেন্ট নেই।</span>
                        @endforelse
                    </div>
                </details>
                @if ($item->isUpload())
                    <div id="v{{ $item->id }}" class="lity-hide vbox"><video controls preload="none" src="{{ route('admin.course-videos.file', $item) }}"></video></div>
                @endif
            </div>
        @empty
            <div class="mute" style="padding:24px">এই কোর্সে এখনো কোনো ক্লাস নেই।</div>
        @endforelse
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
const cvType = document.getElementById('cv_type');
if (cvType) {
    cvType.addEventListener('change', function () {
        document.querySelectorAll('[data-kind]').forEach((el) => { el.hidden = el.dataset.kind !== this.value; });
    });
    // Only the chosen course's batches can be picked.
    document.getElementById('cv_dep').addEventListener('change', function () {
        const sel = document.getElementById('cv_batch');
        sel.querySelectorAll('option[data-dep]').forEach((o) => { o.hidden = o.disabled = o.dataset.dep !== this.value; });
        if (sel.selectedOptions[0] && sel.selectedOptions[0].disabled) sel.value = '';
    });
    ['cv_file', 'cv_files'].forEach((id) => document.getElementById(id).addEventListener('change', function () {
        const label = document.querySelector('label[for="' + id + '"]');
        label.textContent = this.files.length > 1 ? this.files.length + 'টি ফাইল বাছাই হয়েছে' : (this.files[0] ? this.files[0].name : label.dataset.text);
    }));
    // Big uploads take a while — show that something is happening.
    document.getElementById('cv_form').addEventListener('submit', () => { const b = document.getElementById('cv_btn'); b.disabled = true; b.textContent = 'সংরক্ষণ হচ্ছে… অপেক্ষা করুন'; });
}
</script>
<script src="{{ asset('vendor/lity/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/lity/lity.min.js') }}"></script>
<script>
$(document).on('lity:close', (e, lity) => { const v = lity.element().find('video')[0]; if (v) v.pause(); });
</script>
@endpush
