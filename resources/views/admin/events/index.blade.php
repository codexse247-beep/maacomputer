@extends('layouts.admin')

@section('title', 'ক্যালেন্ডার')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ক্যালেন্ডার</strong>@endsection

@php
    $ev = $event ?? new \App\Models\Event(['type' => 'holiday', 'starts_on' => now()]);
    $audience = old('audience', $ev->batch_id ? 'batch' : ($ev->department_id ? 'department' : 'all'));
@endphp

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ক্যালেন্ডার</h1>
        <p class="lead">ছুটি, পরীক্ষা ও অনুষ্ঠান — স্টুডেন্টরা পোর্টালে দেখবে</p>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('admin.events.index', ['month' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}">← আগের মাস</a>
        <b style="min-width:140px;text-align:center">{{ bn_date($month, false) }}</b>
        <a class="btn" href="{{ route('admin.events.index', ['month' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}">পরের মাস →</a>
    </div>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <div style="flex:2 1 520px;min-width:0;display:flex;flex-direction:column;gap:24px">
        <div class="card" style="padding:16px">@include('partials.month-calendar', ['month' => $month, 'events' => $events])</div>
        <div class="card" style="overflow:hidden">
            <div class="sh"><h2>আসন্ন</h2></div>
            @forelse ($upcoming as $e)
                <div style="display:flex;flex-wrap:wrap;gap:8px 16px;padding:12px 24px;border-top:1px solid #DFE5DB;align-items:center">
                    <span class="badge {{ $e->typeTone() }}">{{ $e->typeLabel() }}</span>
                    <span style="flex:1 1 220px;min-width:0;line-height:1.5"><b>{{ $e->title }}</b><br><span class="mute" style="font-size:14px">{{ $e->dateLabel() }} · {{ $e->audienceLabel() }}{{ $e->is_public ? ' · ওয়েবসাইটেও' : '' }}{{ $e->note ? ' · '.$e->note : '' }}</span></span>
                    <a class="el" href="{{ route('admin.events.index', ['month' => $e->starts_on->format('Y-m'), 'edit' => $e->id]) }}">সম্পাদনা</a>
                    <form method="POST" action="{{ route('admin.events.destroy', $e) }}" onsubmit="return confirm('মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>
                </div>
            @empty
                <div class="mute" style="padding:0 24px 24px">সামনে কিছু নেই।</div>
            @endforelse
        </div>
    </div>

    <form method="POST" action="{{ $ev->exists ? route('admin.events.update', $ev) : route('admin.events.store') }}" class="card" style="flex:1 1 320px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:14px">
        @csrf
        @if ($ev->exists) @method('PUT') @endif
        <h2 style="margin:0;font-size:20px;color:#0E3A05">{{ $ev->exists ? 'সম্পাদনা' : 'নতুন যোগ করুন' }}</h2>
        @include('admin.partials.field', ['name' => 'title', 'label' => 'শিরোনাম', 'req' => true, 'value' => $ev->title, 'ph' => 'যেমন: দুর্গাপূজার ছুটি', 'id' => 'e_title'])
        @include('admin.partials.select', ['name' => 'type', 'label' => 'ধরন', 'req' => true, 'value' => $ev->type, 'options' => collect(\App\Models\Event::TYPES)->map(fn ($t) => $t[0])->all(), 'id' => 'e_type'])
        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div style="flex:1 1 140px">@include('admin.partials.field', ['name' => 'starts_on', 'label' => 'তারিখ', 'req' => true, 'type' => 'date', 'value' => $ev->starts_on, 'id' => 'e_start'])</div>
            <div style="flex:1 1 140px">@include('admin.partials.field', ['name' => 'ends_on', 'label' => 'শেষ তারিখ (একাধিক দিন হলে)', 'type' => 'date', 'value' => $ev->ends_on, 'id' => 'e_end'])</div>
        </div>
        <div class="ff">
            <label for="e_aud">কাদের জন্য</label>
            <select class="in" id="e_aud" name="audience">
                <option value="all" @selected($audience === 'all')>সবাই</option>
                <option value="department" @selected($audience === 'department')>নির্দিষ্ট বিভাগ</option>
                <option value="batch" @selected($audience === 'batch')>নির্দিষ্ট ব্যাচ</option>
            </select>
        </div>
        <div class="ff" data-for="department" @if ($audience !== 'department') hidden @endif>
            <label for="e_dep">বিভাগ</label>
            <select class="in" id="e_dep" name="department_id"><option value="">নির্বাচন করুন</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected((int) old('department_id', $ev->department_id) === $d->id)>{{ $d->name }}</option>@endforeach</select>
            @error('department_id')<span class="errm">{{ $message }}</span>@enderror
        </div>
        <div class="ff" data-for="batch" @if ($audience !== 'batch') hidden @endif>
            <label for="e_batch">ব্যাচ</label>
            <select class="in" id="e_batch" name="batch_id"><option value="">নির্বাচন করুন</option>@foreach ($batches as $b)<option value="{{ $b->id }}" @selected((int) old('batch_id', $ev->batch_id) === $b->id)>{{ $b->label() }}</option>@endforeach</select>
            @error('batch_id')<span class="errm">{{ $message }}</span>@enderror
        </div>
        @include('admin.partials.field', ['name' => 'note', 'label' => 'বিস্তারিত (ঐচ্ছিক)', 'value' => $ev->note, 'id' => 'e_note'])
        <label class="sw" data-for="all" @if ($audience !== 'all') hidden @endif><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $ev->is_public))>ওয়েবসাইটের নোটিশ পাতাতেও দেখান</label>
        <div class="row">
            @if ($ev->exists)<a class="btn" href="{{ route('admin.events.index', ['month' => $month->format('Y-m')]) }}">বাতিল</a>@endif
            <button class="btn p">{{ $ev->exists ? 'সংরক্ষণ' : 'যোগ করুন' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('e_aud').addEventListener('change', function () {
    document.querySelectorAll('[data-for]').forEach((el) => { el.hidden = el.dataset.for !== this.value; });
});
</script>
@endpush
