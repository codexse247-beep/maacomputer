@extends('layouts.admin')

@section('title', $department->name)
@section('crumbs')<a href="{{ route('admin.departments.index') }}">বিভাগ</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $department->name }}</strong>@endsection

@section('content')
<div class="card" style="padding:24px;display:flex;flex-direction:column;gap:22px">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px">
        <div style="display:flex;align-items:center;gap:16px">
            <span class="code lg">{{ $department->code }}</span>
            <div>
                <h1 class="h1" style="font-size:30px">{{ $department->name }}</h1>
                <p style="margin:2px 0 0;color:#55635A;font-size:16px">ধাপ ২/৪ · স্টুডেন্ট দেখতে একটি ব্যাচ বেছে নিন</p>
            </div>
        </div>
        <div class="row">
            <a class="btn" href="{{ route('admin.departments.edit', $department) }}">@include('partials.icon', ['n' => 'edit', 'size' => 18])বিভাগ সম্পাদনা</a>
            <a class="btn p" href="{{ route('admin.batches.create', $department) }}">@include('partials.icon', ['n' => 'plus', 'size' => 18, 'w' => 2.4])নতুন ব্যাচ খুলুন</a>
        </div>
    </div>
    <div class="facts">
        <div class="fact"><span>কোর্স</span><b>{{ $department->course_name ?: '—' }}</b></div>
        <div class="fact"><span>মেয়াদ</span><b>{{ $department->duration ?: '—' }}</b></div>
        <div class="fact"><span>কোর্স ফি</span><b>{{ taka($department->course_fee) }}</b></div>
        <div class="fact"><span>ভর্তি ফি</span><b>{{ taka($department->admission_fee) }}</b></div>
        <div class="fact"><span>মডিউল</span><b>{{ $department->modules ?: '—' }}</b></div>
    </div>
</div>

<div class="stats">
    <div class="card statc"><span class="k">মোট ব্যাচ</span><span class="v">{{ bn($total) }}টি</span><span class="s">{{ bn(($counts['running'] ?? 0) + ($counts['enrolling'] ?? 0)) }}টি সক্রিয় · {{ bn($counts['completed'] ?? 0) }}টি সমাপ্ত</span></div>
    <div class="card statc"><span class="k">সক্রিয় স্টুডেন্ট</span><span class="v">{{ bn($activeStudents) }} জন</span><span class="s">চলমান ও আসন্ন ব্যাচে</span></div>
    <div class="card statc"><span class="k">খালি আসন</span><span class="v" style="color:#176008">{{ bn($openSeats) }}টি</span><span class="s">{{ $enrollingCodes ? $enrollingCodes.' এ ভর্তি চলছে' : 'এখন কোনো ব্যাচে ভর্তি চলছে না' }}</span></div>
</div>

<div class="card" style="overflow:hidden">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;padding:14px 16px">
        <a class="tab {{ ! $filter ? 'on' : '' }}" href="{{ route('admin.departments.show', $department) }}">সব ব্যাচ · {{ bn($total) }}</a>
        @foreach (\App\Models\Batch::STATUSES as $key => [$label, $tone])
            <a class="tab {{ $filter === $key ? 'on' : '' }}" href="{{ route('admin.departments.show', [$department, 'status' => $key]) }}">{{ $label }} · {{ bn($counts[$key] ?? 0) }}</a>
        @endforeach
    </div>
    <div class="scroll">
        <table class="tbl" style="min-width:960px">
            <thead><tr><th class="th">ব্যাচ</th><th class="th">ক্লাস রুটিন</th><th class="th">মেয়াদ</th><th class="th">শিক্ষক</th><th class="th">স্টুডেন্ট</th><th class="th">অবস্থা</th><th class="th"></th></tr></thead>
            <tbody>
            @forelse ($batches as $b)
                <tr>
                    <td class="td tall"><a href="{{ route('admin.batches.show', $b) }}" style="text-decoration:none;display:flex;flex-direction:column;line-height:1.4"><b style="font-size:16px;color:#0E3A05">{{ $b->code }}</b><span style="color:#55635A;font-size:14px">{{ $b->name }}</span></a></td>
                    <td class="td tall"><div style="display:flex;flex-direction:column;line-height:1.4"><b style="font-weight:600">{{ $b->days ?: '—' }}</b><span style="color:#55635A;font-size:14px">{{ $b->class_time }}</span></div></td>
                    <td class="td tall"><div style="display:flex;flex-direction:column;line-height:1.4"><b style="font-weight:600">{{ bn_date($b->start_date) }}</b><span style="color:#55635A;font-size:14px">থেকে {{ bn_date($b->end_date) }}</span></div></td>
                    <td class="td tall">{{ $b->teacher ?: '—' }}</td>
                    <td class="td tall"><div style="display:flex;flex-direction:column;gap:6px;min-width:120px"><span style="font-weight:700">{{ bn($b->active_students_count) }} <span style="font-weight:500;color:#55635A">/ {{ bn($b->capacity) }}</span></span><span class="bar thin"><span style="width:{{ min(100, round($b->active_students_count / max(1, $b->capacity) * 100)) }}%"></span></span></div></td>
                    <td class="td tall"><span class="badge {{ $b->statusTone() }}">{{ $b->statusLabel() }}</span></td>
                    <td class="td tall" style="text-align:right"><a class="btn" href="{{ route('admin.batches.show', $b) }}">স্টুডেন্ট দেখুন @include('partials.icon', ['n' => 'chev', 'size' => 16])</a></td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="7">এই বিভাগে এখনো কোনো ব্যাচ নেই। <a href="{{ route('admin.batches.create', $department) }}">নতুন ব্যাচ খুলুন</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
