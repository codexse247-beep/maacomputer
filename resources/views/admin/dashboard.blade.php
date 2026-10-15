@extends('layouts.admin')

@section('title', 'ড্যাশবোর্ড')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ড্যাশবোর্ড</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ড্যাশবোর্ড</h1>
        <p class="lead">{{ bn_weekday(now()) }}, {{ bn_date(now()) }}</p>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('admin.payments.index') }}">পেমেন্ট ও বকেয়া</a>
        <a class="btn p" href="{{ route('admin.students.create') }}">@include('partials.icon', ['n' => 'plus', 'size' => 18, 'w' => 2.4])নতুন ভর্তি</a>
    </div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(min(230px,100%),1fr))">
    <a class="card statc" href="{{ route('admin.students.index', ['status' => 'active']) }}"><span class="k">সক্রিয় স্টুডেন্ট</span><span class="v">{{ bn($activeCount) }} জন</span><span class="s">{{ bn($departmentCount) }}টি বিভাগে</span></a>
    <a class="card statc" href="{{ route('admin.departments.index') }}"><span class="k">চলমান ও আসন্ন ব্যাচ</span><span class="v">{{ bn($openBatchCount) }}টি</span><span class="s">খালি আসন {{ bn($openSeats) }}টি</span></a>
    <div class="card statc"><span class="k">এই মাসে নতুন ভর্তি</span><span class="v" style="color:#176008">{{ bn($newThisMonth) }} জন</span><span class="s">{{ bn_date(now()->startOfMonth(), false) }} থেকে</span></div>
    <a class="card statc" href="{{ route('admin.payments.index') }}#dues"><span class="k">মোট বকেয়া ফি</span><span class="v" style="color:#B00E28">{{ taka($totalDue) }}</span><span class="s">{{ bn($dueCount) }} জন স্টুডেন্টের</span></a>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:stretch">
    <div class="card" style="flex:999 1 520px;min-width:0">
        <div class="sh"><h2>বিভাগ অনুযায়ী সক্রিয় স্টুডেন্ট</h2><a class="btn" href="{{ route('admin.departments.index') }}">সব বিভাগ</a></div>
        <div style="padding:4px 24px 24px;display:flex;flex-direction:column;gap:16px">
            @forelse ($departments as $d)
                <a href="{{ route('admin.departments.show', $d) }}" style="display:grid;grid-template-columns:minmax(110px,230px) minmax(70px,1fr) 32px;align-items:center;gap:14px;text-decoration:none;color:inherit">
                    <span style="font-size:15px;font-weight:500">{{ $d->name }}</span>
                    <span class="bar" style="height:14px"><span style="width:{{ round($d->students_count / $maxDept * 100) }}%"></span></span>
                    <b style="text-align:right">{{ bn($d->students_count) }}</b>
                </a>
            @empty
                <p class="mute" style="margin:0">এখনো কোনো বিভাগ নেই। <a href="{{ route('admin.departments.create') }}">বিভাগ যোগ করুন</a></p>
            @endforelse
        </div>
    </div>

    <div class="card" style="flex:1 1 340px;min-width:0">
        <div class="sh"><h2>মনোযোগ প্রয়োজন</h2></div>
        <div style="padding:4px 20px 24px;display:flex;flex-direction:column;gap:10px">
            @if ($newEnquiries)
            <a class="alert" href="{{ route('admin.enquiries.index') }}"><span class="num" style="background:#176008;color:#fff">{{ bn($newEnquiries) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>নতুন অনলাইন আবেদন ও মেসেজ</b><span style="font-size:14px;color:#55635A">ওয়েবসাইট থেকে এসেছে — ফোন করুন</span></span></a>
            @endif
            <a class="alert" href="{{ route('admin.payments.index', ['fee' => 'due']) }}"><span class="num" style="background:#FCEBEC;color:#B00E28">{{ bn($overdueCount) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>কিস্তির তারিখ পেরিয়ে গেছে</b><span style="font-size:14px;color:#55635A">ফি বকেয়া স্টুডেন্ট</span></span></a>
            <a class="alert" href="{{ route('admin.students.index', ['status' => 'active']) }}"><span class="num" style="background:#FFF3DB;color:#8A5300">{{ bn($missingDocs) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>ভর্তি ফরম জমা বাকি</b><span style="font-size:14px;color:#55635A">স্বাক্ষরিত ফরম আপলোড হয়নি</span></span></a>
            <a class="alert" href="{{ route('admin.departments.index') }}"><span class="num" style="background:#E6EFFA;color:#1F5FA8">{{ bn($nearlyFull) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>ব্যাচের আসন প্রায় পূর্ণ</b><span style="font-size:14px;color:#55635A">২টির কম আসন খালি</span></span></a>
            <a class="alert" href="{{ route('admin.departments.index') }}"><span class="num" style="background:#E3F0DD;color:#176008">{{ bn($endingSoon) }}</span><span style="display:flex;flex-direction:column;line-height:1.4"><b>ব্যাচ শেষ হচ্ছে</b><span style="font-size:14px;color:#55635A">৩০ দিনের মধ্যে · সনদ ও চূড়ান্ত ফলাফল</span></span></a>
        </div>
    </div>
</div>

<div class="card" style="overflow:hidden">
    <div class="sh"><h2>সাম্প্রতিক ভর্তি</h2><a class="btn" href="{{ route('admin.students.index') }}">সব স্টুডেন্ট</a></div>
    <div class="scroll">
        <table class="tbl" style="min-width:760px">
            <thead><tr><th class="th">তারিখ</th><th class="th">স্টুডেন্ট</th><th class="th">বিভাগ</th><th class="th">ব্যাচ</th><th class="th">ফি</th></tr></thead>
            <tbody>
            @forelse ($recent as $r)
                <tr>
                    <td class="td">{{ bn_date($r->admission_date, false) }}</td>
                    <td class="td"><a href="{{ route('admin.students.show', $r) }}" style="font-weight:600;text-decoration:none;color:#0E3A05">{{ $r->name_bn }}</a></td>
                    <td class="td">{{ $r->batch->department->name }}</td>
                    <td class="td">{{ $r->batch->code }}</td>
                    <td class="td"><span class="badge {{ $r->feeTone() }}">{{ $r->feeLabel() }}</span></td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="5">এখনো কোনো ভর্তি হয়নি।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
