@extends('layouts.portal')

@section('title', 'ক্লাস ভিডিও')

@php
    $open = $videos->filter(fn ($v) => ! $v->lockReason($student));
    $done = $open->filter(fn ($v) => $watched->has($v->id))->count();
    $pct = $open->count() ? (int) round($done / $open->count() * 100) : 0;
@endphp

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/lity/lity.min.css') }}">
<style>
.vlist{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(280px,100%),1fr));gap:16px}
.vcard{display:flex;flex-direction:column;background:#fff;border:1px solid #DFE5DB;border-radius:14px;overflow:hidden}
.vcard.done{border-color:#B9D9AD}
.vthumb{position:relative;aspect-ratio:16/9;background:#0E3A05 center/cover no-repeat;display:flex;align-items:center;justify-content:center;text-decoration:none}
a.vthumb:hover .play,a.vthumb:focus-visible .play{transform:scale(1.08)}
.vthumb .play{width:56px;height:56px;border-radius:50%;background:rgba(176,14,40,.92);display:flex;align-items:center;justify-content:center;transition:transform .2s}
.vthumb .play svg{fill:#fff;stroke:none;margin-left:4px}
.vthumb .no{position:absolute;left:10px;top:10px;background:#fff;color:#0E3A05;font-weight:700;font-size:13px;border-radius:99px;padding:3px 10px}
.vthumb .tick{position:absolute;right:10px;top:10px;background:#176008;color:#fff;font-weight:700;font-size:13px;border-radius:99px;padding:3px 10px}
.vthumb.locked{background:#55635A;color:#fff;font-weight:600;font-size:15px;flex-direction:column;gap:6px;text-align:center;padding:0 16px}
.vbody{padding:14px;display:flex;flex-direction:column;gap:8px;flex:1}
.vbody b{font-size:16px;line-height:1.5;color:#14201A}
.vbody p{margin:0;font-size:14px;line-height:1.6;color:#55635A;white-space:pre-line}
.vfile{display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:600;color:#176008;text-decoration:none}
.vfile small{color:#8B958E;font-weight:400}
.wbtn{margin-top:auto;align-self:flex-start;display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 12px;border-radius:10px;border:1px solid #C9D2C5;background:#fff;font:inherit;font-size:14px;font-weight:600;color:#3F4D44;cursor:pointer}
.wbtn[aria-pressed=true]{background:#E3F0DD;border-color:#B9D9AD;color:#176008}
.vbox{width:min(960px,92vw);background:#000}
.vbox video{width:100%;max-height:80vh;display:block}
</style>
@endpush

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ক্লাস ভিডিও</h1>
        <p class="lead">{{ $student->batch->department->course_name ?: $student->batch->department->name }}</p>
    </div>
</div>

@if ($blocked)
    <div class="card" style="padding:24px">আপনার ভর্তি সক্রিয় নেই, তাই ক্লাস ভিডিও দেখা যাচ্ছে না। বিস্তারিত জানতে অফিসে যোগাযোগ করুন।</div>
@elseif ($videos->isEmpty())
    <div class="card" style="padding:24px">এই কোর্সে এখনো কোনো ক্লাস যোগ করা হয়নি।</div>
@else
    <div class="card" style="padding:18px 22px;display:flex;flex-direction:column;gap:8px">
        <span style="font-size:15px;font-weight:600;color:#0E3A05"><span id="doneCount">{{ bn($done) }}</span>/{{ bn($open->count()) }} ক্লাস দেখা হয়েছে</span>
        <span class="bar"><span id="doneBar" style="width:{{ $pct }}%"></span></span>
    </div>

    <div class="vlist">
        @foreach ($videos as $v)
            @php $lock = $v->lockReason($student); $seen = $watched->has($v->id); @endphp
            <article class="vcard {{ $seen ? 'done' : '' }}" data-video="{{ $v->id }}">
                @if ($lock)
                    <div class="vthumb locked">
                        @if ($v->class_no)<span class="no">ক্লাস {{ bn($v->class_no) }}</span>@endif
                        <span style="font-size:28px" aria-hidden="true">🔒</span>{{ $lock }}
                    </div>
                @else
                    <a class="vthumb" href="{{ $v->isUpload() ? '#v'.$v->id : $v->playUrl() }}" data-lity data-kind="{{ $v->type }}" aria-label="ভিডিও দেখুন: {{ $v->title }}" @if ($v->thumb()) style="background-image:url('{{ $v->thumb() }}')" @endif>
                        @if ($v->class_no)<span class="no">ক্লাস {{ bn($v->class_no) }}</span>@endif
                        <span class="tick" @if (! $seen) hidden @endif>✓ দেখা হয়েছে</span>
                        <span class="play" aria-hidden="true">@include('partials.icon', ['n' => 'play', 'size' => 26])</span>
                    </a>
                @endif
                <div class="vbody">
                    <b>{{ $v->title }}</b>
                    @if ($v->description)<p>{{ $v->description }}</p>@endif
                    @if (! $lock)
                        @foreach ($v->files as $f)
                            <a class="vfile" href="{{ route('portal.course-files.download', $f) }}">@include('partials.icon', ['n' => 'download', 'size' => 16]){{ $f->name }} <small>({{ $f->sizeLabel() }})</small></a>
                        @endforeach
                        <form method="POST" action="{{ route('portal.videos.watched', $v) }}" class="wform" style="margin-top:auto">
                            @csrf
                            <input type="hidden" name="watched" value="{{ $seen ? 0 : 1 }}">
                            <button class="wbtn" aria-pressed="{{ $seen ? 'true' : 'false' }}">{{ $seen ? '✓ দেখা হয়েছে' : 'দেখা হয়েছে চিহ্ন দিন' }}</button>
                        </form>
                        @if ($v->isUpload())
                            <div id="v{{ $v->id }}" class="lity-hide vbox">
                                <video controls preload="none" controlslist="nodownload" oncontextmenu="return false" src="{{ route('portal.videos.file', $v) }}" data-video="{{ $v->id }}"></video>
                            </div>
                        @endif
                    @elseif ($v->files->isNotEmpty())
                        <span class="mute" style="font-size:14px">🔒 {{ bn($v->files->count()) }}টি নোট ফাইল</span>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('vendor/lity/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/lity/lity.min.js') }}"></script>
<script>
const total = {{ $open->count() }};
const bnDigits = (n) => String(n).replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[d]);

const pending = new Set();

// Save watched / not watched without reloading the page.
function setWatched(id, on) {
    const card = document.querySelector('.vcard[data-video="' + id + '"]');
    const form = card && card.querySelector('.wform');
    if (!form || pending.has(id) || (form.watched.value === '1') !== on) return; // busy or already in that state
    pending.add(id);
    const body = new FormData(form);
    fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json' } }).finally(() => pending.delete(id)).then((r) => {
        if (!r.ok) return;
        form.watched.value = on ? '0' : '1';
        const btn = form.querySelector('button');
        btn.setAttribute('aria-pressed', on);
        btn.textContent = on ? '✓ দেখা হয়েছে' : 'দেখা হয়েছে চিহ্ন দিন';
        card.classList.toggle('done', on);
        card.querySelector('.tick').hidden = !on;
        const done = document.querySelectorAll('.vcard.done .wform').length; // open classes only, like the server count
        document.getElementById('doneCount').textContent = bnDigits(done);
        document.getElementById('doneBar').style.width = (total ? Math.round(done / total * 100) : 0) + '%';
    });
}
document.querySelectorAll('.wform').forEach((form) => form.addEventListener('submit', (e) => {
    e.preventDefault();
    setWatched(form.closest('.vcard').dataset.video, form.watched.value === '1');
}));

// Uploaded videos count as watched at 90%; links (YouTube etc.) can't report back, so opening them counts.
document.querySelectorAll('video[data-video]').forEach((v) => v.addEventListener('timeupdate', () => {
    if (v.duration && v.currentTime / v.duration >= 0.9) setWatched(v.dataset.video, true);
}));
document.querySelectorAll('a.vthumb[data-kind=url]').forEach((a) => a.addEventListener('click', () => setWatched(a.closest('.vcard').dataset.video, true)));

$(document).on('lity:ready', (e, lity) => { const v = lity.element().find('video')[0]; if (v) v.play().catch(() => {}); });
$(document).on('lity:close', (e, lity) => { const v = lity.element().find('video')[0]; if (v) v.pause(); });
</script>
@endpush
