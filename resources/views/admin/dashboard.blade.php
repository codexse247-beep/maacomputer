@extends('layouts.admin')

@section('title', 'ড্যাশবোর্ড')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ড্যাশবোর্ড</strong>@endsection

@php
    $u = auth()->user();
    $fees = $u->canAccess('fees');
@endphp

@push('head')
<style>
.chart{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;align-items:end;height:190px;padding-top:8px}
.chart .col{display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end;min-width:0}
.chart .bars{display:flex;gap:4px;align-items:flex-end;height:150px;width:100%;justify-content:center}
.chart .b{width:min(22px,40%);border-radius:6px 6px 2px 2px;min-height:2px}
.chart .m{font-size:12px;color:#55635A;white-space:nowrap}
.legend{display:flex;gap:16px;font-size:13px;color:#55635A}
.legend i{display:inline-block;width:12px;height:12px;border-radius:3px;vertical-align:middle;margin-right:4px}
</style>
@endpush

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ড্যাশবোর্ড</h1>
        <p class="lead">{{ bn_weekday(now()) }}, {{ bn_date(now()) }} · স্বাগতম, {{ $u->name }}</p>
    </div>
    <div class="row">
        @if ($fees)<a class="btn" href="{{ route('admin.dues.index') }}">বকেয়া ও রিমাইন্ডার</a>@endif
        @can('area', 'admissions')<a class="btn p" href="{{ route('admin.students.create') }}">@include('partials.icon', ['n' => 'plus', 'size' => 18, 'w' => 2.4])নতুন ভর্তি</a>@endcan
        @if (! $u->canAccess('admissions') && $u->canAccess('classes'))<a class="btn p" href="{{ route('admin.attendance.index') }}">উপস্থিতি নিন</a>@endif
    </div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(min(230px,100%),1fr))">
    <a class="card statc" href="{{ route('admin.students.index', ['status' => 'active']) }}"><span class="k">সক্রিয় স্টুডেন্ট</span><span class="v">{{ bn($activeCount) }} জন</span><span class="s">{{ bn($departmentCount) }}টি বিভাগে</span></a>
    <a class="card statc" href="{{ route('admin.batches.all') }}"><span class="k">চলমান ও আসন্ন ব্যাচ</span><span class="v">{{ bn($openBatchCount) }}টি</span><span class="s">খালি আসন {{ bn($openSeats) }}টি</span></a>
    <div class="card statc"><span class="k">এই মাসে নতুন ভর্তি</span><span class="v" style="color:#176008">{{ bn($newThisMonth) }} জন</span><span class="s">{{ bn_date(now()->startOfMonth(), false) }} থেকে</span></div>
    @if ($fees)
        <a class="card statc" href="{{ route('admin.dues.index') }}"><span class="k">মোট বকেয়া ফি</span><span class="v" style="color:#B00E28">{{ taka($totalDue) }}</span><span class="s">{{ bn($dueCount) }} জন স্টুডেন্টের</span></a>
    @endif
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:stretch">
    @if ($fees)
    <div class="card" style="flex:1 1 400px;min-width:0;padding:20px 24px;display:flex;flex-direction:column;gap:10px">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:center">
            <h2 style="margin:0;font-size:19px;color:#0E3A05">আয় ও খরচ (৬ মাস)</h2>
            <span class="legend"><span><i style="background:#176008"></i>ফি আদায়</span><span><i style="background:#E07A86"></i>খরচ</span></span>
        </div>
        <div class="chart" role="img" aria-label="গত ৬ মাসের আয় ও খরচ">
            @foreach ($months as $m)
                <div class="col" title="{{ $m['label'] }}: আয় {{ taka($m['income']) }}, খরচ {{ taka($m['expense']) }}">
                    <div class="bars">
                        <span class="b" style="height:{{ round($m['income'] / $maxMoney * 100) }}%;background:#176008"></span>
                        <span class="b" style="height:{{ round($m['expense'] / $maxMoney * 100) }}%;background:#E07A86"></span>
                    </div>
                    <span class="m">{{ $m['short'] }}</span>
                </div>
            @endforeach
        </div>
        @php $last = $months->last(); @endphp
        <div class="mute" style="font-size:14px">এই মাসে: আয় <b style="color:#176008">{{ taka($last['income']) }}</b> · খরচ <b style="color:#B00E28">{{ taka($last['expense']) }}</b> · {{ $last['income'] >= $last['expense'] ? 'লাভ' : 'ক্ষতি' }} <b>{{ taka(abs($last['income'] - $last['expense'])) }}</b></div>
    </div>
    @endif

    <div class="card" style="flex:1 1 340px;min-width:0;padding:20px 24px;display:flex;flex-direction:column;gap:10px">
        <h2 style="margin:0;font-size:19px;color:#0E3A05">নতুন ভর্তি (৬ মাস)</h2>
        <div class="chart" role="img" aria-label="গত ৬ মাসের ভর্তি">
            @foreach ($months as $m)
                <div class="col" title="{{ $m['label'] }}: {{ bn($m['admissions']) }} জন">
                    <b style="font-size:13px">{{ bn($m['admissions']) }}</b>
                    <div class="bars" style="height:130px"><span class="b" style="height:{{ round($m['admissions'] / $maxAdmissions * 100) }}%;background:#1F5FA8;width:min(34px,60%)"></span></div>
                    <span class="m">{{ $m['short'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:stretch">
    <div class="card" style="flex:999 1 520px;min-width:0">
        <div class="sh"><h2>বিভাগ অনুযায়ী সক্রিয় স্টুডেন্ট</h2></div>
        <div style="padding:4px 24px 24px;display:flex;flex-direction:column;gap:16px">
            @forelse ($departments as $d)
                <a href="{{ route('admin.departments.show', $d) }}" style="display:grid;grid-template-columns:minmax(110px,230px) minmax(70px,1fr) 32px;align-items:center;gap:14px;text-decoration:none;color:inherit">
                    <span style="font-size:15px;font-weight:500">{{ $d->name }}</span>
                    <span class="bar" style="height:14px"><span style="width:{{ round($d->students_count / $maxDept * 100) }}%"></span></span>
                    <b style="text-align:right">{{ bn($d->students_count) }}</b>
                </a>
            @empty
                <p class="mute" style="margin:0">এখনো কোনো বিভাগ নেই।</p>
            @endforelse
        </div>
    </div>

    <div class="card" style="flex:1 1 340px;min-width:0">
        <div class="sh"><h2>মনোযোগ প্রয়োজন</h2></div>
        <div style="padding:4px 20px 24px;display:flex;flex-direction:column;gap:10px">
            @if ($newEnquiries)
                <a class="alert" href="{{ route('admin.enquiries.index') }}"><span class="num" style="background:#176008;color:#fff">{{ bn($newEnquiries) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>নতুন অনলাইন আবেদন ও মেসেজ</b><span style="font-size:14px;color:#55635A">ওয়েবসাইট থেকে এসেছে — "ভর্তি করুন" চাপুন</span></span></a>
            @endif
            @if ($pendingOnline)
                <a class="alert" href="{{ route('admin.online-payments.index') }}"><span class="num" style="background:#8A5300;color:#fff">{{ bn($pendingOnline) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>অনলাইন পেমেন্ট যাচাই বাকি</b><span style="font-size:14px;color:#55635A">বিকাশ/নগদ অ্যাপে মিলিয়ে গ্রহণ করুন</span></span></a>
            @endif
            @if ($toMark)
                <a class="alert" href="{{ route('admin.assignments.index') }}"><span class="num" style="background:#1F5FA8;color:#fff">{{ bn($toMark) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>অ্যাসাইনমেন্টে নম্বর দেওয়া বাকি</b><span style="font-size:14px;color:#55635A">স্টুডেন্টরা জমা দিয়েছে</span></span></a>
            @endif
            @if ($fees)
                <a class="alert" href="{{ route('admin.dues.index') }}"><span class="num" style="background:#FCEBEC;color:#B00E28">{{ bn($overdueCount) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>কিস্তির তারিখ পেরিয়ে গেছে</b><span style="font-size:14px;color:#55635A">রিমাইন্ডার পাঠান বা ফোন করুন</span></span></a>
            @endif
            @can('area', 'admissions')
                <a class="alert" href="{{ route('admin.students.index', ['status' => 'active']) }}"><span class="num" style="background:#FFF3DB;color:#8A5300">{{ bn($missingDocs) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>ভর্তি ফরম জমা বাকি</b><span style="font-size:14px;color:#55635A">স্বাক্ষরিত ফরম আপলোড হয়নি</span></span></a>
            @endcan
            <a class="alert" href="{{ route('admin.batches.all') }}"><span class="num" style="background:#E6EFFA;color:#1F5FA8">{{ bn($nearlyFull) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>ব্যাচের আসন প্রায় পূর্ণ</b><span style="font-size:14px;color:#55635A">২টির কম আসন খালি</span></span></a>
            <a class="alert" href="{{ route('admin.batches.all') }}"><span class="num" style="background:#E3F0DD;color:#176008">{{ bn($endingSoon) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>ব্যাচ শেষ হচ্ছে</b><span style="font-size:14px;color:#55635A">৩০ দিনের মধ্যে · সনদ ও চূড়ান্ত ফলাফল</span></span></a>
        </div>
    </div>
</div>

@if ($events->isNotEmpty())
<div class="card" style="overflow:hidden">
    <div class="sh"><h2>সামনের ৩০ দিন</h2>@can('area', 'classes')<a class="btn" href="{{ route('admin.events.index') }}">ক্যালেন্ডার</a>@endcan</div>
    @foreach ($events as $e)
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;padding:12px 24px;border-top:1px solid #DFE5DB;align-items:center">
            <span class="badge {{ $e->typeTone() }}">{{ $e->typeLabel() }}</span><b>{{ $e->title }}</b><span class="mute" style="font-size:14px">{{ $e->dateLabel() }} · {{ $e->audienceLabel() }}</span>
        </div>
    @endforeach
</div>
@endif

<div class="card" style="overflow:hidden">
    <div class="sh"><h2>সাম্প্রতিক ভর্তি</h2><a class="btn" href="{{ route('admin.students.index') }}">সব স্টুডেন্ট</a></div>
    <div class="scroll">
        <table class="tbl" style="min-width:680px">
            <thead><tr><th class="th">তারিখ</th><th class="th">স্টুডেন্ট</th><th class="th">বিভাগ</th><th class="th">ব্যাচ</th>@if ($fees)<th class="th">ফি</th>@endif</tr></thead>
            <tbody>
            @forelse ($recent as $r)
                <tr>
                    <td class="td">{{ bn_date($r->admission_date, false) }}</td>
                    <td class="td"><a href="{{ route('admin.students.show', $r) }}" style="font-weight:600;text-decoration:none;color:#0E3A05">{{ $r->name_bn }}</a></td>
                    <td class="td">{{ $r->batch->department->name }}</td>
                    <td class="td">{{ $r->batch->code }}</td>
                    @if ($fees)<td class="td"><span class="badge {{ $r->feeTone() }}">{{ $r->feeLabel() }}</span></td>@endif
                </tr>
            @empty
                <tr><td class="td mute" colspan="5">এখনো কোনো ভর্তি হয়নি।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
