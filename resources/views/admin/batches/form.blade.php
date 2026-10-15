@extends('layouts.admin')

@php $editing = $batch->exists; @endphp
@section('title', $editing ? 'ব্যাচ সম্পাদনা' : 'নতুন ব্যাচ')
@section('crumbs')<a href="{{ route('admin.departments.index') }}">বিভাগ</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<a href="{{ route('admin.departments.show', $department) }}">{{ $department->name }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $editing ? $batch->code : 'নতুন ব্যাচ' }}</strong>@endsection

@section('content')
<div>
    <h1 class="h1">{{ $editing ? 'ব্যাচ সম্পাদনা — '.$batch->code : 'নতুন ব্যাচ — '.$department->name }}</h1>
    <p class="lead">ব্যাচ কোডের সংখ্যা দিয়ে স্টুডেন্ট আইডি তৈরি হয় (যেমন GD-2607 → MCT-2607-001)। <span class="req">*</span> চিহ্নিত ঘর আবশ্যক।</p>
</div>

<form method="POST" action="{{ $editing ? route('admin.batches.update', $batch) : route('admin.batches.store', $department) }}" class="card">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="fgrid" style="padding-top:24px">
        @include('admin.partials.field', ['name' => 'code', 'label' => 'ব্যাচ কোড', 'req' => true, 'value' => $batch->code ?? $department->code.'-'.now()->format('ym'), 'hint' => 'বিভাগ কোড-বছর মাস, যেমন: '.$department->code.'-'.now()->format('ym')])
        @include('admin.partials.field', ['name' => 'name', 'label' => 'ব্যাচের নাম', 'req' => true, 'value' => $batch->name, 'ph' => 'যেমন: সকালের ব্যাচ'])
        @include('admin.partials.field', ['name' => 'days', 'label' => 'ক্লাসের দিন', 'value' => $batch->days, 'ph' => 'যেমন: শনি · সোম · বুধ'])
        @include('admin.partials.field', ['name' => 'class_time', 'label' => 'ক্লাসের সময়', 'value' => $batch->class_time, 'ph' => 'যেমন: সকাল ১০:০০ – দুপুর ১২:০০'])
        @include('admin.partials.field', ['name' => 'start_date', 'label' => 'শুরুর তারিখ', 'type' => 'date', 'value' => $batch->start_date])
        @include('admin.partials.field', ['name' => 'end_date', 'label' => 'শেষের তারিখ', 'type' => 'date', 'value' => $batch->end_date])
        <div class="ff">
            <label for="teacher_id">শিক্ষক</label>
            <select id="teacher_id" name="teacher_id" class="in">
                <option value="">নির্বাচন করুন</option>
                @foreach (\App\Models\Teacher::where('active', true)->orderBy('name')->get() as $t)
                    <option value="{{ $t->id }}" @selected((int) old('teacher_id', $batch->teacher_id) === $t->id)>{{ $t->name }}{{ $t->department_id === $department->id ? '' : ' ('.($t->department?->name ?? 'অন্যান্য').')' }}</option>
                @endforeach
            </select>
            <small>তালিকায় না থাকলে <a href="{{ route('admin.teachers.index') }}">শিক্ষক যোগ করুন</a></small>
        </div>
        @include('admin.partials.field', ['name' => 'room', 'label' => 'রুম', 'value' => $batch->room, 'ph' => 'যেমন: ল্যাব ২'])
        @include('admin.partials.field', ['name' => 'capacity', 'label' => 'আসন সংখ্যা', 'req' => true, 'value' => $batch->capacity, 'mode' => 'numeric'])
        <div class="ff">
            <label for="status">অবস্থা <span class="req">*</span></label>
            <select id="status" name="status" class="in">
                @foreach (\App\Models\Batch::STATUSES as $key => [$label])
                    <option value="{{ $key }}" @selected(old('status', $batch->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #DFE5DB" class="row">
        <a class="btn" href="{{ $editing ? route('admin.batches.show', $batch) : route('admin.departments.show', $department) }}">বাতিল</a>
        <button class="btn p">{{ $editing ? 'সংরক্ষণ' : 'ব্যাচ খুলুন' }}</button>
    </div>
</form>

@if ($editing)
<form method="POST" action="{{ route('admin.batches.destroy', $batch) }}" onsubmit="return confirm('এই ব্যাচটি মুছে ফেলবেন?')">
    @csrf @method('DELETE')
    <button class="btn d">ব্যাচ মুছুন</button>
</form>
@endif
@endsection
