@extends('layouts.portal')

@section('title', $quiz->title)

@php
    $done = (bool) $attempt->submitted_at;
    $answers = $attempt->answers ?? [];
    $left = $done ? null : $attempt->secondsLeft();
@endphp

@push('head')
<style>
.q{display:flex;flex-direction:column;gap:10px;padding:18px 0;border-top:1px solid #DFE5DB}
.q:first-child{border-top:0}
.opt{display:flex;gap:12px;align-items:center;padding:12px 14px;border:1px solid #C9D2C5;border-radius:12px;cursor:pointer;font-size:16px;line-height:1.5}
.opt:hover{background:#F5F7F3}
.opt input{width:22px;height:22px;accent-color:#176008;margin:0;flex:none}
.opt.right{border-color:#176008;background:#E3F0DD;font-weight:700}
.opt.wrong{border-color:#B00E28;background:#FCEBEC}
.timer{position:sticky;top:8px;z-index:5;align-self:flex-end;background:#0E3A05;color:#fff;font-weight:700;font-size:18px;border-radius:99px;padding:8px 18px}
.timer.low{background:#B00E28}
</style>
@endpush

@section('content')
<div><a href="{{ route('portal.study') }}">← অ্যাসাইনমেন্ট ও কুইজ</a></div>
<div class="pagehead">
    <div>
        <h1 class="h1" style="font-size:28px">{{ $quiz->title }}</h1>
        <p class="lead">{{ bn($quiz->questions->count()) }}টি প্রশ্ন{{ $quiz->time_limit ? ' · '.bn($quiz->time_limit).' মিনিট' : '' }}</p>
    </div>
    @if ($done)<span class="badge b-g" style="font-size:18px;height:40px;padding:0 18px">নম্বর {{ bn($attempt->score) }}/{{ bn($attempt->total) }}</span>@endif
</div>
@if ($quiz->description && ! $done)<div class="pcard" style="white-space:pre-line">{{ $quiz->description }}</div>@endif

@if (! $done && $left !== null)
    <div class="timer" id="timer" role="timer" aria-live="off">বাকি সময়: <span id="tleft"></span></div>
@endif

<form method="POST" action="{{ route('portal.quizzes.submit', $quiz) }}" class="pcard" id="quizform" style="display:flex;flex-direction:column">
    @csrf
    @foreach ($quiz->questions as $i => $q)
        @php $mine = $answers[$q->id] ?? null; @endphp
        <fieldset class="q" style="border:0;margin:0">
            <legend style="font-weight:700;font-size:17px;line-height:1.6;white-space:pre-line;padding:0 0 6px">{{ bn($i + 1) }}. {{ $q->question }}</legend>
            @foreach ($q->options as $k => $opt)
                @continue($opt === '')
                @php $cls = $done && $quiz->show_answers ? ($k === $q->correct ? 'right' : ($mine === $k ? 'wrong' : '')) : ''; @endphp
                <label class="opt {{ $cls }}">
                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $k }}" @checked($mine === $k) @disabled($done)>
                    <span>{{ \App\Models\QuizQuestion::LETTERS[$k] }}) {{ $opt }}</span>
                </label>
            @endforeach
            @if ($done && $quiz->show_answers && $mine === null)<span class="mute" style="font-size:14px">উত্তর দেননি</span>@endif
        </fieldset>
    @endforeach
    @unless ($done)
        <div style="padding-top:16px"><button class="btn p" onclick="return confirm('জমা দেবেন? এরপর আর বদলানো যাবে না।')">জমা দিন</button></div>
    @endunless
</form>
@endsection

@if (! $done && $left !== null)
@push('scripts')
<script>
// Counts down from the server's time; when it reaches zero the answers are sent automatically.
let left = {{ $left }};
const out = document.getElementById('tleft'), box = document.getElementById('timer'), form = document.getElementById('quizform');
const bn = (s) => String(s).replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[d]);
function tick() {
    out.textContent = bn(Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0'));
    box.classList.toggle('low', left <= 60);
    if (left <= 0) { form.querySelectorAll('button').forEach((b) => b.onclick = null); form.submit(); return; }
    left--; setTimeout(tick, 1000);
}
tick();
</script>
@endpush
@endif
