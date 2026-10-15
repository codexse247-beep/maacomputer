@extends('layouts.admin')

@section('title', 'সব স্টুডেন্ট')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>সব স্টুডেন্ট</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">{{ $q ? '"'.$q.'" — খোঁজার ফলাফল' : 'সব স্টুডেন্ট' }}</h1>
        <p class="lead">নাম, স্টুডেন্ট আইডি, স্টুডেন্ট বা অভিভাবকের মোবাইল নম্বর দিয়ে খুঁজুন।</p>
    </div>
    <a class="btn p" href="{{ route('admin.students.create') }}">@include('partials.icon', ['n' => 'plus', 'size' => 18, 'w' => 2.4])নতুন ভর্তি</a>
</div>

<div class="card" style="overflow:hidden">
    <form method="GET" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:16px">
        <div class="search" style="width:360px">
            @include('partials.icon', ['n' => 'search', 'size' => 18, 'color' => '#55635A'])
            <input class="input" type="search" name="q" value="{{ $q }}" placeholder="নাম, আইডি বা মোবাইল" aria-label="স্টুডেন্ট খুঁজুন">
        </div>
        <label for="st" class="sr">অবস্থা</label>
        <select id="st" name="status" class="sel" onchange="this.form.submit()">
            <option value="">অবস্থা: সব</option>
            @foreach (\App\Models\Student::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
        </select>
        <button class="btn">খুঁজুন</button>
    </form>
    <div class="scroll">
        <table class="tbl" style="min-width:960px">
            <thead><tr><th class="th">স্টুডেন্ট</th><th class="th">বিভাগ · ব্যাচ</th><th class="th">মোবাইল</th><th class="th">ভর্তির তারিখ</th><th class="th">বকেয়া</th><th class="th">অবস্থা</th><th class="th"></th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                <tr>
                    <td class="td"><a href="{{ route('admin.students.show', $s) }}" style="display:flex;align-items:center;gap:12px;text-decoration:none">
                        @if ($s->photoUrl())<img class="av" src="{{ $s->photoUrl() }}" alt="">@else<span class="av">{{ initial($s->name_bn) }}</span>@endif
                        <span style="display:flex;flex-direction:column;line-height:1.4"><b style="color:#0E3A05">{{ $s->name_bn }}</b><span style="color:#55635A;font-size:13px">{{ $s->student_code }}</span></span></a></td>
                    <td class="td"><div style="display:flex;flex-direction:column;line-height:1.4"><span>{{ $s->batch->department->name }}</span><a href="{{ route('admin.batches.show', $s->batch) }}" style="font-size:14px">{{ $s->batch->code }}</a></div></td>
                    <td class="td">{{ bn($s->phone) }}</td>
                    <td class="td">{{ bn_date($s->admission_date) }}</td>
                    <td class="td"><span class="badge {{ $s->feeTone() }}">{{ $s->totalDue() ? taka($s->totalDue()) : 'পরিশোধিত' }}</span></td>
                    <td class="td"><span class="badge {{ $s->statusTone() }}">{{ $s->statusLabel() }}</span></td>
                    <td class="td" style="text-align:right"><a class="btn" href="{{ route('admin.students.show', $s) }}" aria-label="{{ $s->name_bn }} এর তথ্য খুলুন">@include('partials.icon', ['n' => 'chev', 'size' => 16])</a></td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="7">কোনো স্টুডেন্ট পাওয়া যায়নি।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager" style="display:block">{{ $students->links('partials.pager') }}</div>
</div>
@endsection
