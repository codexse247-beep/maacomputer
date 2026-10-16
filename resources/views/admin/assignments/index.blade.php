@extends('layouts.admin')

@section('title', 'অ্যাসাইনমেন্ট ও কুইজ')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>অ্যাসাইনমেন্ট ও কুইজ</strong>@endsection

@php
    $a = $assignment ?? new \App\Models\Assignment(['department_id' => $course?->id, 'max_marks' => 10, 'due_on' => now()->addWeek()]);
    $dep = (int) old('department_id', $a->department_id);
    $batchSelect = function (string $id, ?int $value) use ($batches, $dep) {
        $html = '<select class="in" id="'.$id.'" name="batch_id" data-batches><option value="">কোর্সের সব ব্যাচ</option>';
        foreach ($batches as $b) {
            $html .= '<option value="'.$b->id.'" data-dep="'.$b->department_id.'"'.((int) old('batch_id', $value) === $b->id ? ' selected' : '').($b->department_id !== $dep ? ' hidden disabled' : '').'>'.e($b->label()).'</option>';
        }

        return $html.'</select>';
    };
@endphp

@section('content')
<div>
    <h1 class="h1">অ্যাসাইনমেন্ট ও কুইজ</h1>
    <p class="lead">বাড়ির কাজ দিন, জমা নিন ও নম্বর দিন · MCQ কুইজ নিন — স্টুডেন্টরা পোর্টালে করবে</p>
</div>

@if ($departments->isEmpty())
    <div class="card" style="padding:24px">আগে একটি বিভাগ (কোর্স) তৈরি করুন।</div>
@else
<nav style="display:flex;flex-wrap:wrap;gap:8px">
    @foreach ($departments as $d)
        <a class="btn sm {{ $course->id === $d->id ? 'p' : '' }}" href="{{ route('admin.assignments.index', ['course' => $d->id, 'tab' => $tab]) }}">{{ $d->name }}</a>
    @endforeach
</nav>
<nav style="display:flex;gap:4px;padding:6px;background:#fff;border:1px solid #DFE5DB;border-radius:14px;width:max-content;max-width:100%">
    <a class="tab {{ $tab === 'assignment' ? 'on' : '' }}" href="{{ route('admin.assignments.index', ['course' => $course->id]) }}">অ্যাসাইনমেন্ট · {{ bn($assignments->count()) }}</a>
    <a class="tab {{ $tab === 'quiz' ? 'on' : '' }}" href="{{ route('admin.assignments.index', ['course' => $course->id, 'tab' => 'quiz']) }}">কুইজ · {{ bn($quizzes->count()) }}</a>
</nav>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
@if ($tab === 'assignment')
    <form method="POST" action="{{ $a->exists ? route('admin.assignments.update', $a) : route('admin.assignments.store') }}" enctype="multipart/form-data" class="card" style="flex:1 1 340px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:16px">
        @csrf
        @if ($a->exists) @method('PUT') @endif
        <h2 style="margin:0;font-size:20px;color:#0E3A05">{{ $a->exists ? 'অ্যাসাইনমেন্ট সম্পাদনা' : 'নতুন অ্যাসাইনমেন্ট' }}</h2>
        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div style="flex:1 1 150px">@include('admin.partials.select', ['name' => 'department_id', 'label' => 'কোর্স', 'req' => true, 'value' => $a->department_id, 'options' => $departments->pluck('name', 'id')->all(), 'id' => 'as_dep'])</div>
            <div class="ff" style="flex:1 1 150px"><label for="as_batch">কোন ব্যাচ</label>{!! $batchSelect('as_batch', $a->batch_id) !!}@error('batch_id')<span class="errm">{{ $message }}</span>@enderror</div>
        </div>
        @include('admin.partials.field', ['name' => 'title', 'label' => 'শিরোনাম', 'req' => true, 'value' => $a->title, 'ph' => 'যেমন: একটি লোগো ডিজাইন করুন', 'id' => 'as_title'])
        <div class="ff"><label for="as_ins">নির্দেশনা</label><textarea class="ta" id="as_ins" name="instructions" style="min-height:110px" placeholder="কী করতে হবে, কোন ফরম্যাটে জমা দিতে হবে">{{ old('instructions', $a->instructions) }}</textarea></div>
        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div style="flex:1 1 150px">@include('admin.partials.field', ['name' => 'due_on', 'label' => 'জমার শেষ তারিখ', 'type' => 'date', 'value' => $a->due_on, 'id' => 'as_due'])</div>
            <div style="flex:1 1 110px">@include('admin.partials.field', ['name' => 'max_marks', 'label' => 'পূর্ণ নম্বর', 'req' => true, 'value' => $a->max_marks, 'mode' => 'numeric', 'id' => 'as_max'])</div>
        </div>
        @include('admin.partials.upload', ['name' => 'file', 'label' => $a->file_name ? 'সংযুক্ত ফাইল: '.$a->file_name.' (বদলাতে নতুন বাছুন)' : 'সংযুক্ত ফাইল (ঐচ্ছিক)', 'btn' => 'ফাইল বাছুন', 'accept' => '.'.str_replace(',', ',.', \App\Models\Assignment::EXTENSIONS), 'id' => 'as_file'])
        <div class="row">
            @if ($a->exists)<a class="btn" href="{{ route('admin.assignments.show', $a) }}">বাতিল</a>@endif
            <button class="btn p">{{ $a->exists ? 'সংরক্ষণ' : 'অ্যাসাইনমেন্ট দিন' }}</button>
        </div>
    </form>

    <div class="card" style="flex:2 1 440px;min-width:0;overflow:hidden">
        @forelse ($assignments as $x)
            <a href="{{ route('admin.assignments.show', $x) }}" style="display:flex;flex-wrap:wrap;gap:8px 16px;padding:16px 24px;border-bottom:1px solid #DFE5DB;text-decoration:none;color:inherit;align-items:center">
                <span style="flex:1 1 240px;min-width:0;line-height:1.5"><b style="color:#0E3A05">{{ $x->title }}</b><br>
                    <span class="mute" style="font-size:14px">{{ $x->batch ? 'শুধু '.$x->batch->code : 'সব ব্যাচ' }} · পূর্ণ নম্বর {{ bn($x->max_marks) }}{{ $x->due_on ? ' · শেষ তারিখ '.bn_date($x->due_on) : '' }}</span></span>
                <span class="badge {{ $x->isClosed() ? 'b-n' : 'b-g' }}">{{ $x->isClosed() ? 'সময় শেষ' : 'চলছে' }}</span>
                <span class="badge b-b">জমা {{ bn($x->submissions_count) }}</span>
            </a>
        @empty
            <div class="mute" style="padding:24px">এই কোর্সে এখনো কোনো অ্যাসাইনমেন্ট নেই।</div>
        @endforelse
    </div>
@else
    <form method="POST" action="{{ route('admin.quizzes.store') }}" class="card" style="flex:1 1 340px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:16px">
        @csrf
        <h2 style="margin:0;font-size:20px;color:#0E3A05">নতুন কুইজ</h2>
        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div style="flex:1 1 150px">@include('admin.partials.select', ['name' => 'department_id', 'label' => 'কোর্স', 'req' => true, 'value' => $course->id, 'options' => $departments->pluck('name', 'id')->all(), 'id' => 'as_dep'])</div>
            <div class="ff" style="flex:1 1 150px"><label for="qz_batch">কোন ব্যাচ</label>{!! $batchSelect('qz_batch', null) !!}</div>
        </div>
        @include('admin.partials.field', ['name' => 'title', 'label' => 'শিরোনাম', 'req' => true, 'value' => '', 'ph' => 'যেমন: ফটোশপ মডিউল কুইজ', 'id' => 'qz_title'])
        <div style="display:flex;flex-wrap:wrap;gap:16px">
            <div style="flex:1 1 130px">@include('admin.partials.field', ['name' => 'time_limit', 'label' => 'সময় (মিনিট)', 'value' => 15, 'mode' => 'numeric', 'hint' => 'খালি = সময়ের সীমা নেই', 'id' => 'qz_time'])</div>
            <div style="flex:1 1 150px">@include('admin.partials.field', ['name' => 'closes_on', 'label' => 'শেষ তারিখ', 'type' => 'date', 'value' => '', 'id' => 'qz_close'])</div>
        </div>
        <label class="sw"><input type="checkbox" name="show_answers" value="1" checked>জমার পর সঠিক উত্তর দেখাবে</label>
        <div><button class="btn p">তৈরি করে প্রশ্ন যোগ করুন</button></div>
    </form>

    <div class="card" style="flex:2 1 440px;min-width:0;overflow:hidden">
        @forelse ($quizzes as $q)
            <a href="{{ route('admin.quizzes.show', $q) }}" style="display:flex;flex-wrap:wrap;gap:8px 16px;padding:16px 24px;border-bottom:1px solid #DFE5DB;text-decoration:none;color:inherit;align-items:center">
                <span style="flex:1 1 240px;min-width:0;line-height:1.5"><b style="color:#0E3A05">{{ $q->title }}</b><br>
                    <span class="mute" style="font-size:14px">{{ $q->batch ? 'শুধু '.$q->batch->code : 'সব ব্যাচ' }} · {{ bn($q->questions_count) }}টি প্রশ্ন{{ $q->time_limit ? ' · '.bn($q->time_limit).' মিনিট' : '' }}{{ $q->closes_on ? ' · শেষ '.bn_date($q->closes_on) : '' }}</span></span>
                <span class="badge {{ $q->published ? ($q->isClosed() ? 'b-n' : 'b-g') : 'b-a' }}">{{ $q->published ? ($q->isClosed() ? 'সময় শেষ' : 'প্রকাশিত') : 'খসড়া' }}</span>
                <span class="badge b-b">দিয়েছে {{ bn($q->attempts_count) }}</span>
            </a>
        @empty
            <div class="mute" style="padding:24px">এই কোর্সে এখনো কোনো কুইজ নেই।</div>
        @endforelse
    </div>
@endif
</div>
@endif
@endsection

@push('scripts')
<script>
// Only the chosen course's batches can be picked.
const dep = document.getElementById('as_dep');
if (dep) dep.addEventListener('change', function () {
    document.querySelectorAll('[data-batches]').forEach((sel) => {
        sel.querySelectorAll('option[data-dep]').forEach((o) => { o.hidden = o.disabled = o.dataset.dep !== this.value; });
        if (sel.selectedOptions[0] && sel.selectedOptions[0].disabled) sel.value = '';
    });
});
const f = document.getElementById('as_file');
if (f) f.addEventListener('change', function () { const l = document.querySelector('label[for="as_file"]'); l.textContent = this.files[0] ? this.files[0].name : l.dataset.text; });
</script>
@endpush
