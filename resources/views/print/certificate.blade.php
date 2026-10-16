@extends('print.layout')

@section('title', 'সনদ — '.$student->name_en)

@php
    $dept = $student->batch->department;
    $start = $student->batch->start_date;
    $end = $student->batch->end_date;
    $verify = route('verify', ['certificate_no' => $student->certificate_no, 'dob' => $student->dob?->format('Y-m-d')]);
    $nameEn = setting('name_en');
    $en = fn ($d) => $d ? $d->format('d F Y') : '';
@endphp

@push('head')
<style>
@media print{@page{size:A4 landscape;margin:0}body{background:#fff}}
.cert{width:297mm;height:210mm;max-width:100%;margin:0 auto 24px;background:#fff;position:relative;box-sizing:border-box;padding:12mm;font-family:'Hind Siliguri',serif;color:#14201A}
.cert .frame{height:100%;box-sizing:border-box;border:3px solid #176008;outline:1px solid #176008;outline-offset:-10px;border-radius:6px;padding:14mm 18mm;display:flex;flex-direction:column;align-items:center;text-align:center;
  background:radial-gradient(circle at 50% 45%,rgba(23,96,8,.05),transparent 60%)}
.cert .org{font-size:24pt;font-weight:700;color:#0E3A05;line-height:1.2}
.cert .org-en{font-size:13pt;letter-spacing:.06em;color:#3F4D44;text-transform:uppercase}
.cert .title{margin:8mm 0 2mm;font-size:30pt;font-weight:700;color:#176008;letter-spacing:.04em}
.cert .sub{font-size:13pt;color:#55635A}
.cert .name{margin:6mm 0 1mm;font-size:26pt;font-weight:700;border-bottom:1.5px solid #9DBB93;padding:0 12mm 2mm}
.cert .name-bn{font-size:15pt;color:#3F4D44}
.cert p{font-size:13.5pt;line-height:1.75;max-width:220mm;margin:4mm 0 0}
.cert .foot{margin-top:auto;width:100%;display:flex;justify-content:space-between;align-items:flex-end;gap:10mm;font-size:11pt}
.cert .sign{width:60mm;border-top:1.2px solid #14201A;padding-top:2mm}
.cert .meta{text-align:center;line-height:1.6;color:#3F4D44}
</style>
@endpush

@section('content')
<div class="cert">
    <div class="frame">
        <img src="{{ logo_url() }}" alt="" style="width:22mm;height:22mm;border-radius:50%;object-fit:cover;margin-bottom:3mm">
        <div class="org">{{ setting('name') }}</div>
        <div class="org-en">{{ $nameEn }}</div>
        <div class="sub">{{ setting('area') }}</div>

        <div class="title">Certificate of Completion</div>
        <div class="sub">সনদপত্র</div>

        <div class="sub" style="margin-top:6mm">This is to certify that</div>
        <div class="name">{{ $student->name_en }}</div>
        <div class="name-bn">{{ $student->name_bn }}</div>

        <p>
            {{ $student->gender === 'মহিলা' ? 'daughter' : 'son' }} of <b>{{ $student->father_name }}</b>, Student ID <b>{{ $student->student_code }}</b>,
            has successfully completed the course <b>{{ $dept->course_name ?: $dept->name }}</b>@if ($dept->duration) ({{ $dept->duration }})@endif
            @if ($start && $end) from <b>{{ $en($start) }}</b> to <b>{{ $en($end) }}</b>@endif
            (Batch {{ $student->batch->code }})@if ($grade !== '—') with grade <b>{{ $grade }}</b>@endif.
        </p>

        <div class="foot">
            <div class="sign">প্রশিক্ষক / Instructor</div>
            <div class="meta">
                Certificate No: <b>{{ $student->certificate_no }}</b><br>
                Issued on: {{ $en($student->certificate_issued_on) }}<br>
                <span style="font-size:9.5pt">Verify: {{ $verify }}</span>
            </div>
            <div class="sign">পরিচালক / Director</div>
        </div>
    </div>
</div>
@endsection
