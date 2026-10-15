@extends('layouts.portal')

@section('title', 'আমার প্রোফাইল')

@section('content')
<div>
    <h1 class="h1">আমার প্রোফাইল</h1>
    <p class="lead">{{ $student->student_code }} · {{ $student->batch->label() }}</p>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <div class="pcard" style="flex:1 1 320px;min-width:0">
        <h2 style="margin:0 0 16px;font-size:22px;color:#0E3A05">ব্যক্তিগত তথ্য</h2>
        <dl class="dl2">
            <dt>নাম</dt><dd>{{ $student->name_bn }} ({{ $student->name_en }})</dd>
            <dt>স্টুডেন্ট আইডি</dt><dd>{{ $student->student_code }}</dd>
            <dt>বিভাগ ও ব্যাচ</dt><dd>{{ $student->batch->department->name }} · {{ $student->batch->code }}</dd>
            <dt>রোল</dt><dd>{{ bn($student->roll) }}</dd>
            <dt>ভর্তির তারিখ</dt><dd>{{ bn_date($student->admission_date) }}</dd>
            <dt>জন্ম তারিখ</dt><dd>{{ bn_date($student->dob) }}</dd>
            <dt>মোবাইল</dt><dd>{{ bn($student->phone) }}</dd>
            <dt>পিতার নাম</dt><dd>{{ $student->father_name }}</dd>
            <dt>মাতার নাম</dt><dd>{{ $student->mother_name }}</dd>
            <dt>অভিভাবকের মোবাইল</dt><dd>{{ bn($student->guardian_phone) }}</dd>
            <dt>ঠিকানা</dt><dd>{{ $student->presentAddress() }}</dd>
            @if ($student->certificate_no)<dt>সনদ নম্বর</dt><dd>{{ $student->certificate_no }}</dd>@endif
        </dl>
        <p style="margin:16px 0 0;font-size:14px;color:#55635A">তথ্য বদলাতে চাইলে অফিসে জানান।</p>
    </div>

    <form method="POST" action="{{ route('portal.password') }}" class="pcard" style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:16px">
        @csrf
        <h2 style="margin:0;font-size:22px;color:#0E3A05">পাসওয়ার্ড পরিবর্তন</h2>
        <div class="ff"><label for="p1">বর্তমান পাসওয়ার্ড</label><input class="in" id="p1" type="password" name="current_password" required autocomplete="current-password"></div>
        <div class="ff"><label for="p2">নতুন পাসওয়ার্ড</label><input class="in" id="p2" type="password" name="password" required minlength="6" autocomplete="new-password"><small>কমপক্ষে ৬ অক্ষর</small></div>
        <div class="ff"><label for="p3">নতুন পাসওয়ার্ড আবার</label><input class="in" id="p3" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="btn p" type="submit">পরিবর্তন করুন</button>
    </form>
</div>
@endsection
