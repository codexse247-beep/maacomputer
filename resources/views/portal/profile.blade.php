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
        <p style="margin:16px 0 0;font-size:14px;color:#55635A">নাম, জন্ম তারিখ বা কোর্সের তথ্য বদলাতে অফিসে জানান। মোবাইল, ইমেইল, ঠিকানা ও ছবি নিজেই বদলাতে পারবেন।</p>
    </div>

    <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="pcard" style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:14px">
        @csrf @method('PUT')
        <h2 style="margin:0;font-size:22px;color:#0E3A05">যোগাযোগ ও ছবি</h2>
        @foreach ([['phone', 'মোবাইল', 'tel', true], ['whatsapp', 'হোয়াটসঅ্যাপ (ঐচ্ছিক)', 'tel', false], ['email', 'ইমেইল (রসিদ ও নোটিশ এখানে আসবে)', 'email', false]] as [$name, $label, $type, $req])
            <div class="ff">
                <label for="pf_{{ $name }}">{{ $label }}</label>
                <input class="in {{ $errors->has($name) ? 'bad' : '' }}" id="pf_{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $student->$name) }}" @if ($req) required @endif>
                @error($name)<span class="errm">{{ $message }}</span>@enderror
            </div>
        @endforeach
        <fieldset style="border:0;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(min(150px,100%),1fr));gap:12px">
            <legend class="lbl" style="margin-bottom:6px">বর্তমান ঠিকানা</legend>
            @foreach ([['present_village', 'গ্রাম / রোড / বাসা', true], ['present_po', 'ডাকঘর', false], ['present_upazila', 'উপজেলা', true], ['present_district', 'জেলা', true]] as [$name, $label, $req])
                <div class="ff">
                    <label for="pf_{{ $name }}">{{ $label }}</label>
                    <input class="in {{ $errors->has($name) ? 'bad' : '' }}" id="pf_{{ $name }}" name="{{ $name }}" value="{{ old($name, $student->$name) }}" @if ($req) required @endif>
                    @error($name)<span class="errm">{{ $message }}</span>@enderror
                </div>
            @endforeach
        </fieldset>
        <div class="ff">
            <label for="pf_photo">ছবি (পাসপোর্ট সাইজ, ২ MB পর্যন্ত)</label>
            <input class="in" id="pf_photo" type="file" name="photo" accept="image/*" style="padding:10px">
            @error('photo')<span class="errm">{{ $message }}</span>@enderror
        </div>
        <button class="btn p" type="submit">সংরক্ষণ</button>
    </form>

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
