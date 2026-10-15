@extends('print.layout')

@section('title', 'ভর্তি ফরম — '.$student->student_code)

@php
    $rows = [
        ['স্টুডেন্ট আইডি', $student->student_code], ['রোল', bn($student->roll)],
        ['নাম (বাংলা)', $student->name_bn], ['Name (English)', $student->name_en],
        ['পিতার নাম', $student->father_name], ['মাতার নাম', $student->mother_name],
        ['জন্ম তারিখ', bn_date($student->dob)], ['লিঙ্গ', $student->gender],
        ['রক্তের গ্রুপ', $student->blood_group], ['ধর্ম', $student->religion],
        ['জাতীয়তা', $student->nationality], ['এনআইডি / জন্মনিবন্ধন', $student->nid ? bn($student->nid) : null],
        ['মোবাইল', bn($student->phone)], ['অভিভাবকের মোবাইল', bn($student->guardian_phone)],
        ['ইমেইল', $student->email], ['জরুরি যোগাযোগ', trim($student->emergency_name.' '.($student->emergency_phone ? bn($student->emergency_phone) : ''))],
        ['বিভাগ', $student->batch->department->name], ['ব্যাচ', $student->batch->label()],
        ['ভর্তির তারিখ', bn_date($student->admission_date)], ['ভর্তির ধরন', $student->admission_type],
        ['মোট প্রদেয়', taka($student->totalPayable())], ['কিস্তি', bn($student->installments).'টি'],
    ];
@endphp

@section('content')
<div class="printpage" style="display:flex;flex-direction:column;gap:18px;font-size:14px">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:2px solid #176008;padding-bottom:14px">
        <div style="display:flex;align-items:center;gap:14px">
            <img src="{{ logo_url() }}" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover">
            <div style="display:flex;flex-direction:column;line-height:1.4">
                <b style="font-size:20px;color:#0E3A05">মা কম্পিউটার এন্ড ট্রেনিং সেন্টার</b>
                <span class="mute" style="font-size:13px">বারইয়ারহাট পৌরসভা, মীরসরাই, চট্টগ্রাম</span>
                <b style="font-size:16px;margin-top:4px">ভর্তি ফরম</b>
            </div>
        </div>
        @if ($student->photoUrl())<img src="{{ $student->photoUrl() }}" alt="" style="width:30mm;height:36mm;object-fit:cover;border:1px solid #DFE5DB">@else<div style="width:30mm;height:36mm;border:1px dashed #9DBB93;display:flex;align-items:center;justify-content:center;color:#55635A">ছবি</div>@endif
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(300px,100%),1fr));gap:0 24px">
        @foreach ($rows as [$l, $v])
            <div style="display:flex;gap:8px;padding:7px 0;border-bottom:1px dotted #C9D2C5"><span class="mute" style="min-width:130px">{{ $l }}</span><b>{{ filled($v) ? $v : '—' }}</b></div>
        @endforeach
    </div>
    <div style="display:flex;gap:8px;padding:7px 0;border-bottom:1px dotted #C9D2C5"><span class="mute" style="min-width:130px">বর্তমান ঠিকানা</span><b>{{ $student->presentAddress() }}</b></div>
    <div style="display:flex;gap:8px;padding:7px 0;border-bottom:1px dotted #C9D2C5"><span class="mute" style="min-width:130px">স্থায়ী ঠিকানা</span><b>{{ $student->permanentAddress() }}</b></div>

    @if (! empty($student->education))
    <div class="scroll"><table class="tbl" style="font-size:13px;min-width:560px">
        <thead><tr><th class="th">পরীক্ষা</th><th class="th">প্রতিষ্ঠান</th><th class="th">গ্রুপ</th><th class="th">বোর্ড</th><th class="th">সন</th><th class="th">ফলাফল</th></tr></thead>
        <tbody>@foreach ($student->education as $e)<tr><td class="td" style="height:36px">{{ $e['exam'] }}</td><td class="td" style="height:36px">{{ $e['inst'] ?? '' }}</td><td class="td" style="height:36px">{{ $e['group'] ?? '' }}</td><td class="td" style="height:36px">{{ $e['board'] ?? '' }}</td><td class="td" style="height:36px">{{ bn($e['year'] ?? '') }}</td><td class="td" style="height:36px">{{ $e['result'] ?? '' }}</td></tr>@endforeach</tbody>
    </table></div>
    @endif

    <p style="margin:8px 0 0;line-height:1.7">আমি এই মর্মে অঙ্গীকার করছি যে, উপরের সকল তথ্য সঠিক এবং প্রতিষ্ঠানের সকল নিয়ম-কানুন মেনে চলব।</p>
    <div style="display:flex;flex-wrap:wrap;gap:40px 16px;justify-content:space-between;margin-top:48px">
        <span style="border-top:1px solid #14201A;padding-top:6px;min-width:180px;text-align:center">স্টুডেন্টের স্বাক্ষর</span>
        <span style="border-top:1px solid #14201A;padding-top:6px;min-width:180px;text-align:center">অভিভাবকের স্বাক্ষর</span>
        <span style="border-top:1px solid #14201A;padding-top:6px;min-width:180px;text-align:center">কর্তৃপক্ষের স্বাক্ষর</span>
    </div>
</div>
@endsection
