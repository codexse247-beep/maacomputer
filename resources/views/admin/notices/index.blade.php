@extends('layouts.admin')

@section('title', 'নোটিশ')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>নোটিশ</strong>@endsection

@php
    $n = $notice ?? new \App\Models\Notice(['published_on' => now(), 'tag' => 'admission', 'is_public' => true]);
    $audience = old('audience', $n->batch_id ? 'batch' : ($n->department_id ? 'department' : 'all'));
@endphp

@section('content')
<div>
    <h1 class="h1">নোটিশ</h1>
    <p class="lead">স্টুডেন্ট ও ওয়েবসাইটের জন্য নোটিশ প্রকাশ</p>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <form method="POST" action="{{ $n->exists ? route('admin.notices.update', $n) : route('admin.notices.store') }}" class="card" style="flex:1 1 380px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:16px">
        @csrf
        @if ($n->exists) @method('PUT') @endif
        <h2 style="margin:0;font-size:20px;color:#0E3A05">{{ $n->exists ? 'নোটিশ সম্পাদনা' : 'নতুন নোটিশ' }}</h2>
        @include('admin.partials.field', ['name' => 'title', 'label' => 'শিরোনাম', 'req' => true, 'value' => $n->title, 'ph' => 'নোটিশের শিরোনাম', 'id' => 'nt'])
        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div class="ff" style="flex:1 1 180px">
                <label for="na">কাদের জন্য</label>
                <select class="in" id="na" name="audience">
                    <option value="all" @selected($audience === 'all')>সবাই</option>
                    <option value="department" @selected($audience === 'department')>নির্দিষ্ট বিভাগ</option>
                    <option value="batch" @selected($audience === 'batch')>নির্দিষ্ট ব্যাচ</option>
                </select>
            </div>
            <div style="flex:1 1 180px">@include('admin.partials.select', ['name' => 'tag', 'label' => 'ধরন', 'req' => true, 'value' => $n->tag, 'options' => collect(\App\Models\Notice::TAGS)->map(fn ($t) => $t[0])->all(), 'id' => 'nc'])</div>
        </div>
        <div class="ff" data-for="department" @if ($audience !== 'department') hidden @endif>
            <label for="ndp">বিভাগ</label>
            <select class="in" id="ndp" name="department_id"><option value="">নির্বাচন করুন</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected((int) old('department_id', $n->department_id) === $d->id)>{{ $d->name }}</option>@endforeach</select>
        </div>
        <div class="ff" data-for="batch" @if ($audience !== 'batch') hidden @endif>
            <label for="nbt">ব্যাচ</label>
            <select class="in" id="nbt" name="batch_id"><option value="">নির্বাচন করুন</option>@foreach ($batches as $b)<option value="{{ $b->id }}" @selected((int) old('batch_id', $n->batch_id) === $b->id)>{{ $b->label() }}</option>@endforeach</select>
        </div>
        @include('admin.partials.field', ['name' => 'published_on', 'label' => 'তারিখ', 'req' => true, 'type' => 'date', 'value' => $n->published_on, 'id' => 'nd'])
        <div class="ff"><label for="nb">বিবরণ</label><textarea class="ta" id="nb" name="body" placeholder="নোটিশের লেখা" style="min-height:120px">{{ old('body', $n->body) }}</textarea></div>
        <label class="sw" data-for="all" @if ($audience !== 'all') hidden @endif><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $n->is_public))>ওয়েবসাইটেও দেখান</label>
        <div class="row">
            @if ($n->exists)<a class="btn" href="{{ route('admin.notices.index') }}">বাতিল</a>@endif
            <button class="btn p">{{ $n->exists ? 'সংরক্ষণ' : 'প্রকাশ করুন' }}</button>
        </div>
    </form>

    <div class="card" style="flex:1 1 440px;min-width:0;overflow:hidden">
        <div style="padding:18px 24px;border-bottom:1px solid #DFE5DB"><b style="font-size:18px;color:#0E3A05">প্রকাশিত নোটিশ</b></div>
        @forelse ($notices as $item)
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;padding:16px 24px;border-bottom:1px solid #DFE5DB">
                <span style="flex:1 1 220px;min-width:0;display:flex;flex-direction:column;line-height:1.5"><b>{{ $item->title }}</b><span class="mute" style="font-size:14px">{{ bn_date($item->published_on) }} · {{ $item->audienceLabel() }}</span></span>
                <span class="badge {{ $item->tagTone() }}">{{ $item->tagLabel() }}</span>
                <a class="btn sm" href="{{ route('admin.notices.index', ['edit' => $item->id]) }}">সম্পাদনা</a>
                <form method="POST" action="{{ route('admin.notices.destroy', $item) }}" onsubmit="return confirm('নোটিশটি মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>
            </div>
        @empty
            <div class="mute" style="padding:24px">এখনো কোনো নোটিশ নেই।</div>
        @endforelse
        <div class="pager" style="display:block">{{ $notices->links('partials.pager') }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('na').addEventListener('change', function () {
    document.querySelectorAll('[data-for]').forEach((el) => { el.hidden = el.dataset.for !== this.value; });
});
</script>
@endpush
