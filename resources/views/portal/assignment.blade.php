@extends('layouts.portal')

@section('title', $assignment->title)

@php $locked = $submission?->reviewed_at || ($submission && $assignment->isClosed()); @endphp

@section('content')
<div><a href="{{ route('portal.study') }}">← অ্যাসাইনমেন্ট ও কুইজ</a></div>

<div class="pcard" style="display:flex;flex-direction:column;gap:10px">
    <h1 class="h1" style="font-size:28px">{{ $assignment->title }}</h1>
    <div class="mute">পূর্ণ নম্বর {{ bn($assignment->max_marks) }}{{ $assignment->due_on ? ' · জমার শেষ তারিখ '.bn_date($assignment->due_on) : '' }}{{ $assignment->isClosed() ? ' (সময় শেষ)' : '' }}</div>
    @if ($assignment->instructions)<div style="white-space:pre-line;line-height:1.8">{{ $assignment->instructions }}</div>@endif
    @if ($assignment->file_name)<a class="vfile" style="font-weight:600;color:#176008" href="{{ route('portal.assignments.file', $assignment) }}">⬇ {{ $assignment->file_name }}</a>@endif
</div>

@if ($submission)
<div class="pcard" style="display:flex;flex-direction:column;gap:10px;{{ $submission->reviewed_at ? 'border-color:#B9D9AD' : '' }}">
    <h2 style="margin:0;font-size:20px;color:#0E3A05">আপনার জমা</h2>
    <div class="mute" style="font-size:14px">জমা দিয়েছেন {{ bn_date($submission->updated_at) }}{{ $submission->isLate() ? ' (দেরিতে)' : '' }}</div>
    @if ($submission->body)<div style="white-space:pre-line;background:#F5F7F3;border-radius:10px;padding:12px 14px">{{ $submission->body }}</div>@endif
    @if ($submission->file_name)<a style="font-weight:600;color:#176008" href="{{ route('portal.submissions.file', $submission) }}">⬇ {{ $submission->file_name }}</a>@endif
    @if ($submission->reviewed_at)
        <div style="font-size:20px"><b style="color:#176008">নম্বর: {{ bn($submission->marks) }}/{{ bn($assignment->max_marks) }}</b></div>
        @if ($submission->feedback)<div>শিক্ষকের মন্তব্য: <b>{{ $submission->feedback }}</b></div>@endif
    @endif
</div>
@endif

@unless ($locked)
<form method="POST" action="{{ route('portal.assignments.submit', $assignment) }}" enctype="multipart/form-data" class="pcard" style="display:flex;flex-direction:column;gap:14px">
    @csrf
    <h2 style="margin:0;font-size:20px;color:#0E3A05">{{ $submission ? 'জমা বদলান' : 'জমা দিন' }}</h2>
    <div class="ff">
        <label for="sb">উত্তর লিখুন</label>
        <textarea class="ta" id="sb" name="body" style="min-height:140px">{{ old('body', $submission?->body) }}</textarea>
        @error('body')<span class="errm">{{ $message }}</span>@enderror
    </div>
    <div class="ff">
        <label for="sf">অথবা / এবং ফাইল দিন (সর্বোচ্চ ২০ MB)</label>
        <input class="in" type="file" id="sf" name="file" style="padding:10px" accept=".{{ str_replace(',', ',.', \App\Models\Assignment::EXTENSIONS) }}">
        @if ($submission?->file_name)<small>এখন আছে: {{ $submission->file_name }} — নতুন ফাইল দিলে বদলে যাবে</small>@endif
        @error('file')<span class="errm">{{ $message }}</span>@enderror
    </div>
    <div><button class="btn p">জমা দিন</button></div>
</form>
@endunless
@endsection
