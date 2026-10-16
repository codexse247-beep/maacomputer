@extends('layouts.admin')

@php $editing = $department->exists; @endphp
@section('title', $editing ? 'বিভাগ সম্পাদনা' : 'নতুন বিভাগ')
@section('crumbs')<a href="{{ route('admin.departments.index') }}">বিভাগ</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $editing ? $department->name : 'নতুন বিভাগ' }}</strong>@endsection

@section('content')
<div>
    <h1 class="h1">{{ $editing ? 'বিভাগ সম্পাদনা' : 'নতুন বিভাগ' }}</h1>
    <p class="lead">কোর্স ফি ও ভর্তি ফি নতুন ভর্তিতে স্বয়ংক্রিয়ভাবে বসবে। <span class="req">*</span> চিহ্নিত ঘর আবশ্যক।</p>
</div>

<form method="POST" action="{{ $editing ? route('admin.departments.update', $department) : route('admin.departments.store') }}" class="card">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="fgrid" style="padding-top:24px">
        @include('admin.partials.field', ['name' => 'code', 'label' => 'কোড', 'req' => true, 'value' => $department->code, 'hint' => 'ইংরেজি ২–৪ অক্ষর, যেমন: GD'])
        @include('admin.partials.field', ['name' => 'name', 'label' => 'বিভাগের নাম', 'req' => true, 'value' => $department->name])
        @include('admin.partials.field', ['name' => 'course_name', 'label' => 'কোর্সের নাম', 'value' => $department->course_name, 'hint' => 'যেমন: গ্রাফিক ডিজাইন প্রফেশনাল'])
        @include('admin.partials.field', ['name' => 'duration', 'label' => 'মেয়াদ', 'value' => $department->duration, 'hint' => 'যেমন: ৬ মাস'])
        @include('admin.partials.field', ['name' => 'course_fee', 'label' => 'কোর্স ফি (৳)', 'req' => true, 'value' => $department->course_fee ?? 0, 'mode' => 'numeric'])
        @include('admin.partials.field', ['name' => 'admission_fee', 'label' => 'ভর্তি ফি (৳)', 'req' => true, 'value' => $department->admission_fee ?? 0, 'mode' => 'numeric'])
        @include('admin.partials.field', ['name' => 'modules', 'label' => 'মডিউল', 'value' => $department->modules, 'hint' => 'যেমন: ফটোশপ · ইলাস্ট্রেটর · ব্র্যান্ডিং'])
        @include('admin.partials.field', ['name' => 'topics', 'label' => 'ওয়েবসাইটে দেখানো বিষয়', 'value' => $department->topics, 'hint' => 'কমা দিয়ে আলাদা করুন'])
        @include('admin.partials.field', ['name' => 'routine_note', 'label' => 'ক্লাস রুটিন (ওয়েবসাইটে)', 'value' => $department->routine_note, 'ph' => 'যেমন: সপ্তাহে ৩ দিন'])
        @include('admin.partials.field', ['name' => 'sort', 'label' => 'ক্রম', 'value' => $department->sort ?? 0, 'mode' => 'numeric', 'hint' => 'ছোট সংখ্যা আগে দেখাবে'])
    </div>
    <div style="padding:0 24px 16px" class="ff">
        <label for="learn">কী শিখবেন (প্রতি লাইনে একটি — ওয়েবসাইটে দেখাবে)</label>
        <textarea id="learn" name="learn" class="ta" rows="4">{{ old('learn', $department->learn) }}</textarea>
    </div>
    <div style="padding:0 24px 24px" class="ff">
        <label for="blurb">ওয়েবসাইটের জন্য সংক্ষিপ্ত বিবরণ</label>
        <textarea id="blurb" name="blurb" class="ta" maxlength="500">{{ old('blurb', $department->blurb) }}</textarea>
    </div>
    @include('admin.partials.english', ['model' => $department, 'fields' => [
        'name' => ['Department name', 'text'], 'course_name' => ['Course name', 'text'], 'duration' => ['Duration (e.g. 6 months)', 'text'],
        'routine_note' => ['Class routine (e.g. 3 days a week)', 'text'], 'topics' => ['Topics (comma separated)', 'text'],
        'blurb' => ['Short description', 'textarea'], 'learn' => ['What you will learn (one per line)', 'textarea'],
    ]])
    <div style="padding:16px 24px;border-top:1px solid #DFE5DB;display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px">
        <div class="row">
            <a class="btn" href="{{ $editing ? route('admin.departments.show', $department) : route('admin.departments.index') }}">বাতিল</a>
            <button class="btn p">{{ $editing ? 'সংরক্ষণ' : 'বিভাগ যোগ করুন' }}</button>
        </div>
    </div>
</form>

@if ($editing)
<form method="POST" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('এই বিভাগটি মুছে ফেলবেন?')">
    @csrf @method('DELETE')
    <button class="btn d">বিভাগ মুছুন</button>
</form>
@endif
@endsection
