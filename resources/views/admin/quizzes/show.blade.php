@extends('layouts.admin')

@section('title', $quiz->title)
@section('crumbs')<a href="{{ route('admin.assignments.index', ['course' => $quiz->department_id, 'tab' => 'quiz']) }}">কুইজ</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $quiz->title }}</strong>@endsection

@php
    $done = $quiz->attempts->whereNotNull('submitted_at')->sortByDesc('score');
    $locked = $quiz->attempts->isNotEmpty();
@endphp

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1" style="font-size:28px">{{ $quiz->title }} <span class="badge {{ $quiz->published ? 'b-g' : 'b-a' }}">{{ $quiz->published ? 'প্রকাশিত' : 'খসড়া' }}</span></h1>
        <p class="lead">{{ $quiz->audienceLabel() }} · {{ bn($quiz->questions->count()) }}টি প্রশ্ন · দিয়েছে {{ bn($done->count()) }} জন</p>
    </div>
    <div class="row">
        @if ($done->isNotEmpty())<form method="POST" action="{{ route('admin.quizzes.results', $quiz) }}" onsubmit="return confirm('কুইজের নম্বর স্টুডেন্টদের ফলাফলে যোগ হবে। চালিয়ে যাবেন?')">@csrf<button class="btn">নম্বর ফলাফলে যোগ করুন</button></form>@endif
        <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" onsubmit="return confirm('কুইজ, প্রশ্ন ও সবার উত্তর মুছে যাবে। নিশ্চিত?')">@csrf @method('DELETE')<button class="btn d">মুছুন</button></form>
    </div>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
<div style="flex:2 1 460px;min-width:0;display:flex;flex-direction:column;gap:24px">
    <section class="card" style="overflow:hidden">
        <div class="sh"><h2>প্রশ্ন</h2>@if ($locked)<span class="mute" style="font-size:14px">স্টুডেন্টরা দেওয়া শুরু করেছে — প্রশ্ন আর বদলানো যাবে না</span>@endif</div>
        @forelse ($quiz->questions as $i => $q)
            <div style="padding:14px 24px;border-top:1px solid #DFE5DB;display:flex;gap:16px;align-items:flex-start">
                <b style="flex:none">{{ bn($i + 1) }}.</b>
                <div style="flex:1;min-width:0;line-height:1.6">
                    <div style="white-space:pre-line;font-weight:600">{{ $q->question }}</div>
                    @foreach ($q->options as $k => $opt)
                        @if ($opt !== '')<div style="{{ $k === $q->correct ? 'color:#176008;font-weight:700' : '' }}">{{ \App\Models\QuizQuestion::LETTERS[$k] }}) {{ $opt }} {{ $k === $q->correct ? '✓' : '' }}</div>@endif
                    @endforeach
                </div>
                @unless ($locked)<form method="POST" action="{{ route('admin.quiz-questions.destroy', $q) }}" onsubmit="return confirm('প্রশ্নটি মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>@endunless
            </div>
        @empty
            <div class="mute" style="padding:0 24px 24px">এখনো কোনো প্রশ্ন নেই — নিচে যোগ করুন।</div>
        @endforelse
    </section>

    @unless ($locked)
    <form id="add" method="POST" action="{{ route('admin.quizzes.questions', $quiz) }}" class="card" style="padding:24px;display:flex;flex-direction:column;gap:14px">
        @csrf
        <h2 style="margin:0;font-size:20px;color:#0E3A05">নতুন প্রশ্ন</h2>
        <div class="ff"><label for="qq">প্রশ্ন</label><textarea class="ta" id="qq" name="question" required style="min-height:70px">{{ old('question') }}</textarea>@error('question')<span class="errm">{{ $message }}</span>@enderror</div>
        <fieldset style="border:0;margin:0;padding:0;display:flex;flex-direction:column;gap:10px">
            <legend class="lbl" style="margin-bottom:6px">উত্তর — সঠিকটির পাশের বৃত্তে চাপ দিন</legend>
            @foreach (\App\Models\QuizQuestion::LETTERS as $k => $letter)
                <div style="display:flex;gap:10px;align-items:center">
                    <input type="radio" name="correct" value="{{ $k }}" id="qc{{ $k }}" @checked((string) old('correct', '0') === (string) $k) style="width:22px;height:22px;accent-color:#176008" aria-label="{{ $letter }} সঠিক উত্তর">
                    <label for="qo{{ $k }}" style="font-weight:700;width:20px">{{ $letter }}</label>
                    <input class="in" id="qo{{ $k }}" name="options[{{ $k }}]" value="{{ old('options.'.$k) }}" {{ $k < 2 ? 'required' : '' }} placeholder="{{ $k < 2 ? '' : 'ঐচ্ছিক' }}">
                </div>
            @endforeach
            @error('options')<span class="errm">{{ $message }}</span>@enderror
            @error('correct')<span class="errm">{{ $message }}</span>@enderror
        </fieldset>
        <div><button class="btn p">প্রশ্ন যোগ করুন</button></div>
    </form>
    @endunless

    <section class="card" style="overflow:hidden">
        <div class="sh"><h2>ফলাফল</h2></div>
        <div class="scroll">
            <table class="tbl" style="min-width:560px">
                <thead><tr><th class="th">স্টুডেন্ট</th><th class="th">নম্বর</th><th class="th">জমার সময়</th><th class="th"></th></tr></thead>
                <tbody>
                @forelse ($quiz->attempts->sortByDesc('score') as $at)
                    <tr>
                        <td class="td"><b>{{ $at->student->name_bn }}</b> <span class="mute" style="font-size:13px">{{ $at->student->batch->code }}</span></td>
                        <td class="td">@if ($at->submitted_at)<b>{{ bn($at->score) }}/{{ bn($at->total) }}</b> <span class="badge {{ grade_for($at->score / max(1, $at->total) * 100)[1] }}">{{ grade_for($at->score / max(1, $at->total) * 100)[0] }}</span>@else<span class="mute">চলছে…</span>@endif</td>
                        <td class="td">{{ $at->submitted_at ? bn_date($at->submitted_at, false).', '.bn($at->submitted_at->format('h:i A')) : '—' }}</td>
                        <td class="td" style="text-align:right"><form method="POST" action="{{ route('admin.quizzes.reset', [$quiz, $at->student_id]) }}" onsubmit="return confirm('{{ $at->student->name_bn }} এর উত্তর মুছে আবার দেওয়ার সুযোগ দেবেন?')">@csrf<button class="el">আবার দিতে দিন</button></form></td>
                    </tr>
                @empty
                    <tr><td class="td mute" colspan="4">এখনো কেউ কুইজ দেয়নি।</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($missing->isNotEmpty() && $quiz->published)
            <div style="padding:14px 24px;border-top:1px solid #DFE5DB;font-size:14px" class="mute">দেয়নি: {{ $missing->pluck('name_bn')->implode(', ') }}</div>
        @endif
    </section>
</div>

<form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}" class="card" style="flex:1 1 300px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:14px">
    @csrf @method('PUT')
    <h2 style="margin:0;font-size:20px;color:#0E3A05">কুইজের তথ্য</h2>
    @include('admin.partials.field', ['name' => 'title', 'label' => 'শিরোনাম', 'req' => true, 'value' => $quiz->title, 'id' => 'qz_title'])
    <div class="ff"><label for="qz_desc">নির্দেশনা (ঐচ্ছিক)</label><textarea class="ta" id="qz_desc" name="description" style="min-height:70px">{{ old('description', $quiz->description) }}</textarea></div>
    <div class="ff">
        <label for="qz_batch">কোন ব্যাচ</label>
        <select class="in" id="qz_batch" name="batch_id"><option value="">কোর্সের সব ব্যাচ</option>@foreach ($batches as $b)<option value="{{ $b->id }}" @selected((int) old('batch_id', $quiz->batch_id) === $b->id)>{{ $b->label() }}</option>@endforeach</select>
    </div>
    @include('admin.partials.field', ['name' => 'time_limit', 'label' => 'সময় (মিনিট)', 'value' => $quiz->time_limit, 'mode' => 'numeric', 'hint' => 'খালি = সময়ের সীমা নেই', 'id' => 'qz_time'])
    @include('admin.partials.field', ['name' => 'closes_on', 'label' => 'শেষ তারিখ', 'type' => 'date', 'value' => $quiz->closes_on, 'id' => 'qz_close'])
    <label class="sw"><input type="checkbox" name="show_answers" value="1" @checked(old('show_answers', $quiz->show_answers))>জমার পর সঠিক উত্তর দেখাবে</label>
    <label class="sw"><input type="checkbox" name="published" value="1" @checked(old('published', $quiz->published))><b>প্রকাশ করুন</b> (স্টুডেন্টরা পোর্টালে দেখবে)</label>
    @error('published')<span class="errm">{{ $message }}</span>@enderror
    <button class="btn p">সংরক্ষণ</button>
</form>
</div>
@endsection
