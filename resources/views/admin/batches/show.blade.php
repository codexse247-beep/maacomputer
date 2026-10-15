@extends('layouts.admin')

@section('title', $batch->code)
@section('crumbs')<a href="{{ route('admin.departments.index') }}">বিভাগ</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<a href="{{ route('admin.departments.show', $batch->department) }}">{{ $batch->department->name }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $batch->code }}</strong>@endsection

@section('content')
<div class="card" style="padding:24px;display:flex;flex-direction:column;gap:22px">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px">
        <div style="display:flex;align-items:center;gap:16px">
            <span class="code lg">{{ $batch->department->code }}</span>
            <div>
                <div class="row" style="gap:12px"><h1 class="h1" style="font-size:30px">{{ $batch->label() }}</h1><span class="badge {{ $batch->statusTone() }}">{{ $batch->statusLabel() }}</span></div>
                <p style="margin:2px 0 0;color:#55635A;font-size:16px">ধাপ ৩/৪ · সম্পূর্ণ তথ্য দেখতে একজন স্টুডেন্ট খুলুন</p>
            </div>
        </div>
        <div class="row">
            <a class="btn" href="{{ route('admin.attendance.create', $batch) }}">উপস্থিতি নিন</a>
            <a class="btn" href="{{ route('admin.batches.id-cards', $batch) }}" target="_blank">আইডি কার্ড প্রিন্ট</a>
            <a class="btn" href="{{ route('admin.batches.export', $batch) }}">এক্সপোর্ট</a>
            <a class="btn" href="{{ route('admin.batches.edit', $batch) }}">@include('partials.icon', ['n' => 'edit', 'size' => 18])সম্পাদনা</a>
            <a class="btn p" href="{{ route('admin.students.create', ['batch' => $batch->id]) }}">@include('partials.icon', ['n' => 'plus', 'size' => 18, 'w' => 2.4])স্টুডেন্ট ভর্তি করুন</a>
        </div>
    </div>
    <div class="facts">
        <div class="fact"><span>কোর্স</span><b>{{ $batch->department->course_name ?: $batch->department->name }}</b></div>
        <div class="fact"><span>ক্লাস রুটিন</span><b>{{ collect([$batch->days, $batch->class_time])->filter()->implode(', ') ?: '—' }}</b></div>
        <div class="fact"><span>মেয়াদ</span><b>{{ bn_date($batch->start_date) }} – {{ bn_date($batch->end_date) }}</b></div>
        <div class="fact"><span>শিক্ষক</span><b>{{ $batch->teacher ?: '—' }}</b></div>
        <div class="fact"><span>রুম</span><b>{{ $batch->room ?: '—' }}</b></div>
    </div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr))">
    <div class="card statc"><span class="k">ভর্তি হয়েছে</span><span class="v">{{ bn($enrolled) }} <small>/ {{ bn($batch->capacity) }} আসন</small></span></div>
    <div class="card statc"><span class="k">গড় উপস্থিতি</span><span class="v">{{ $avgAttendance === null ? '—' : bn($avgAttendance).'%' }}</span></div>
    <div class="card statc"><span class="k">ফি সম্পূর্ণ পরিশোধ</span><span class="v" style="color:#176008">{{ bn($paidCount) }} <small>জন</small></span></div>
    <div class="card statc"><span class="k">ফি বকেয়া আছে</span><span class="v" style="color:#B00E28">{{ bn($dueCount) }} <small>জন</small></span></div>
</div>

<div class="card" style="overflow:hidden">
    <form method="GET" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:16px">
        <div class="search" style="width:320px">
            @include('partials.icon', ['n' => 'search', 'size' => 18, 'color' => '#55635A'])
            <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="নাম, আইডি বা মোবাইল নম্বর দিয়ে খুঁজুন" aria-label="এই ব্যাচের স্টুডেন্ট খুঁজুন">
        </div>
        <div class="row">
            <label for="f-fee" class="sr">ফি-এর অবস্থা</label>
            <select id="f-fee" name="fee" class="sel" onchange="this.form.submit()">
                <option value="">ফি: সব</option>
                @foreach (\App\Models\Student::FEE_STATES as $k => [$l])<option value="{{ $k }}" @selected(request('fee') === $k)>{{ $l }}</option>@endforeach
            </select>
            <label for="f-st" class="sr">স্টুডেন্টের অবস্থা</label>
            <select id="f-st" name="status" class="sel" onchange="this.form.submit()">
                <option value="">অবস্থা: সব</option>
                @foreach (\App\Models\Student::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
            </select>
            <button class="btn">খুঁজুন</button>
        </div>
    </form>
    <div class="scroll">
        <table class="tbl" style="min-width:1060px">
            <thead><tr><th class="th">রোল</th><th class="th">স্টুডেন্ট</th><th class="th">পিতার নাম</th><th class="th">মোবাইল</th><th class="th">ভর্তির তারিখ</th><th class="th">ফি</th><th class="th">উপস্থিতি</th><th class="th">অবস্থা</th><th class="th"></th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                @php $att = $s->attendanceStats(); @endphp
                <tr>
                    <td class="td"><b>{{ bn(str_pad($s->roll, 2, '0', STR_PAD_LEFT)) }}</b></td>
                    <td class="td"><a href="{{ route('admin.students.show', $s) }}" style="display:flex;align-items:center;gap:12px;text-decoration:none">
                        @if ($s->photoUrl())<img class="av" src="{{ $s->photoUrl() }}" alt="">@else<span class="av">{{ initial($s->name_bn) }}</span>@endif
                        <span style="display:flex;flex-direction:column;line-height:1.4"><b style="color:#0E3A05;font-size:15px">{{ $s->name_bn }}</b><span style="color:#55635A;font-size:13px">{{ $s->student_code }}</span></span></a></td>
                    <td class="td">{{ $s->father_name }}</td>
                    <td class="td">{{ bn($s->phone) }}</td>
                    <td class="td">{{ bn_date($s->admission_date) }}</td>
                    <td class="td"><span class="badge {{ $s->feeTone() }}">{{ $s->feeLabel() }}</span></td>
                    <td class="td">
                        @if ($att['percent'] === null)<span class="mute">—</span>
                        @else<div style="display:flex;align-items:center;gap:10px;min-width:110px"><b style="min-width:42px">{{ bn($att['percent']) }}%</b><span class="bar thin {{ $att['percent'] < 80 ? 'warn' : '' }}" style="flex:1"><span style="width:{{ $att['percent'] }}%"></span></span></div>@endif
                    </td>
                    <td class="td"><span class="badge {{ $s->statusTone() }}">{{ $s->statusLabel() }}</span></td>
                    <td class="td" style="text-align:right"><a class="btn" href="{{ route('admin.students.show', $s) }}" aria-label="{{ $s->name_bn }} এর তথ্য খুলুন">@include('partials.icon', ['n' => 'chev', 'size' => 16])</a></td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="9">কোনো স্টুডেন্ট পাওয়া যায়নি।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager"><span class="mute" style="font-size:15px">{{ bn($students->count()) }} জন দেখানো হচ্ছে</span></div>
</div>
@endsection
