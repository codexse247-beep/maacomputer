@extends('layouts.admin')

@section('title', $assignment->title)
@section('crumbs')<a href="{{ route('admin.assignments.index', ['course' => $assignment->department_id]) }}">অ্যাসাইনমেন্ট</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $assignment->title }}</strong>@endsection

@php $subs = $assignment->submissions->sortBy(fn ($s) => [$s->student->batch_id, $s->student->roll]); @endphp

@section('content')
<div class="card" style="padding:24px;display:flex;flex-wrap:wrap;gap:16px 24px;align-items:flex-start;justify-content:space-between">
    <div style="flex:1 1 360px;min-width:0;display:flex;flex-direction:column;gap:8px">
        <h1 class="h1" style="font-size:28px">{{ $assignment->title }}</h1>
        <div class="mute">{{ $assignment->audienceLabel() }} · পূর্ণ নম্বর {{ bn($assignment->max_marks) }}{{ $assignment->due_on ? ' · জমার শেষ তারিখ '.bn_date($assignment->due_on) : '' }}</div>
        @if ($assignment->instructions)<div style="white-space:pre-line;line-height:1.7">{{ $assignment->instructions }}</div>@endif
        @if ($assignment->file_name)<a class="el" style="padding:0" href="{{ route('admin.assignments.file', $assignment) }}">@include('partials.icon', ['n' => 'download', 'size' => 16]){{ $assignment->file_name }}</a>@endif
    </div>
    <div class="row">
        <a class="btn" href="{{ route('admin.assignments.index', ['edit' => $assignment->id]) }}">সম্পাদনা</a>
        <form method="POST" action="{{ route('admin.assignments.results', $assignment) }}" onsubmit="return confirm('নম্বর দেওয়া জমাগুলো স্টুডেন্টদের ফলাফলে যোগ হবে। চালিয়ে যাবেন?')">@csrf<button class="btn p">নম্বর ফলাফলে যোগ করুন</button></form>
        <form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}" onsubmit="return confirm('অ্যাসাইনমেন্ট ও সব জমা মুছে যাবে। নিশ্চিত?')">@csrf @method('DELETE')<button class="btn d">মুছুন</button></form>
    </div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr))">
    <div class="card statc"><span class="k">জমা দিয়েছে</span><span class="v">{{ bn($subs->count()) }}/{{ bn($eligible) }}</span></div>
    <div class="card statc"><span class="k">নম্বর দেওয়া হয়েছে</span><span class="v" style="color:#176008">{{ bn($subs->whereNotNull('marks')->count()) }}</span></div>
    <div class="card statc"><span class="k">গড় নম্বর</span><span class="v">{{ $subs->whereNotNull('marks')->isNotEmpty() ? bn(round($subs->whereNotNull('marks')->avg('marks'), 1)) : '—' }}</span></div>
</div>

<section class="card" style="overflow:hidden">
    <div class="sh"><h2>জমা</h2></div>
    @forelse ($subs as $s)
        <div id="s{{ $s->id }}" style="display:flex;flex-wrap:wrap;gap:12px 24px;padding:18px 24px;border-top:1px solid #DFE5DB;align-items:flex-start">
            <div style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:6px;line-height:1.6">
                <div><a href="{{ route('admin.students.show', $s->student) }}" style="font-weight:700;color:#0E3A05;text-decoration:none">{{ $s->student->name_bn }}</a> <span class="mute">· {{ $s->student->student_code }} · {{ bn_date($s->created_at) }}</span>
                    @if ($s->isLate())<span class="badge b-a" style="height:24px">দেরিতে</span>@endif</div>
                @if ($s->body)<div style="white-space:pre-line;background:#F5F7F3;border-radius:10px;padding:10px 12px">{{ $s->body }}</div>@endif
                @if ($s->file_name)<a class="el" style="padding:0;height:auto" href="{{ route('admin.submissions.file', $s) }}">@include('partials.icon', ['n' => 'download', 'size' => 16]){{ $s->file_name }}</a>@endif
            </div>
            <form method="POST" action="{{ route('admin.submissions.grade', $s) }}" style="flex:1 1 300px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                @csrf @method('PUT')
                <div class="ff" style="flex:0 0 110px"><label for="m{{ $s->id }}">নম্বর (/{{ bn($assignment->max_marks) }})</label><input class="in" id="m{{ $s->id }}" name="marks" inputmode="numeric" required value="{{ $s->marks }}"></div>
                <div class="ff" style="flex:1 1 160px"><label for="f{{ $s->id }}">মন্তব্য</label><input class="in" id="f{{ $s->id }}" name="feedback" value="{{ $s->feedback }}" placeholder="ভালো হয়েছে…"></div>
                <button class="btn {{ $s->reviewed_at ? '' : 'p' }}">{{ $s->reviewed_at ? 'হালনাগাদ' : 'নম্বর দিন' }}</button>
            </form>
        </div>
    @empty
        <div class="mute" style="padding:0 24px 24px">এখনো কেউ জমা দেয়নি।</div>
    @endforelse
</section>

@if ($missing->isNotEmpty())
<section class="card">
    <div class="sh"><h2>জমা দেয়নি · {{ bn($missing->count()) }} জন</h2></div>
    <div style="padding:0 24px 24px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px 16px">
        @foreach ($missing as $m)<span>{{ $m->name_bn }} <span class="mute" style="font-size:13px">({{ $m->batch->code }} · {{ bn($m->phone) }})</span></span>@endforeach
    </div>
</section>
@endif
@endsection
