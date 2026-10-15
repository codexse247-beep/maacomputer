@extends('layouts.admin')

@section('title', 'বিভাগ')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>বিভাগ</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">বিভাগ</h1>
        <p class="lead">এখান থেকে শুরু করুন। একটি বিভাগ খুলে তার ব্যাচ, তারপর প্রতিটি ব্যাচের স্টুডেন্ট দেখুন।</p>
    </div>
    <a class="btn p" href="{{ route('admin.departments.create') }}">@include('partials.icon', ['n' => 'plus', 'size' => 18, 'w' => 2.4])বিভাগ যোগ করুন</a>
</div>

@include('admin.partials.stepper', ['at' => 1])

<div style="display:flex;flex-wrap:wrap;gap:8px 28px;color:#55635A;font-size:16px;font-weight:500">
    <span><b style="color:#0E3A05">{{ bn($departments->count()) }}টি</b> বিভাগ</span>
    <span><b style="color:#0E3A05">{{ bn($batchCount) }}টি</b> ব্যাচ</span>
    <span><b style="color:#0E3A05">{{ bn($studentCount) }} জন</b> সক্রিয় স্টুডেন্ট</span>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(340px,100%),1fr));gap:20px">
    @foreach ($departments as $d)
        @php
            $open = $d->batches->whereIn('status', ['running', 'enrolling']);
            $cap = $open->sum('capacity');
            $filled = $open->sum('active_students_count');
            $latest = $d->batches->first();
        @endphp
        <a class="card dept" href="{{ route('admin.departments.show', $d) }}">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                <div style="display:flex;align-items:center;gap:14px">
                    <span class="code">{{ $d->code }}</span>
                    <span style="font-size:19px;font-weight:700;line-height:1.4;color:#0E3A05">{{ $d->name }}</span>
                </div>
                @include('partials.icon', ['n' => 'chev', 'color' => '#55635A', 'style' => 'flex:none'])
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
                <div class="stat"><b>{{ bn($d->batches_count) }}</b><span>ব্যাচ</span></div>
                <div class="stat"><b>{{ bn($d->students_count) }}</b><span>সক্রিয় স্টুডেন্ট</span></div>
                <div class="stat"><b>{{ bn(max(0, $cap - $filled)) }}</b><span>খালি আসন</span></div>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <span class="bar"><span style="width:{{ $cap ? round($filled / $cap * 100) : 0 }}%"></span></span>
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:4px 12px;font-size:14px;color:#55635A">
                    <span>সর্বশেষ: <b style="color:#14201A">{{ $latest ? $latest->label() : 'কোনো ব্যাচ নেই' }}</b></span>
                    @if ($latest)<span>{{ $latest->statusLabel() }}</span>@endif
                </div>
            </div>
        </a>
    @endforeach
    <a class="dashed" href="{{ route('admin.departments.create') }}">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:#E3F0DD">@include('partials.icon', ['n' => 'plus', 'w' => 2.4])</span>
        বিভাগ যোগ করুন
    </a>
</div>
@endsection
