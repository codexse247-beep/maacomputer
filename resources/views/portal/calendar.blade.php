@extends('layouts.portal')

@section('title', 'ক্যালেন্ডার')

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ক্যালেন্ডার</h1>
        <p class="lead">ক্লাস রুটিন: {{ $student->batch->days ?: 'শীঘ্রই জানানো হবে' }}{{ $student->batch->class_time ? ' · '.$student->batch->class_time : '' }}</p>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('portal.calendar', ['month' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}">←</a>
        <b style="min-width:130px;text-align:center">{{ bn_date($month, false) }}</b>
        <a class="btn" href="{{ route('portal.calendar', ['month' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}">→</a>
    </div>
</div>

<div class="pcard" style="padding:14px">
    @include('partials.month-calendar', ['month' => $month, 'events' => $events, 'classDay' => $classDay])
    <div style="display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:12px;font-size:14px">
        <span><span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:#F1F7EE;border:1px solid #DFE5DB;vertical-align:middle"></span> ক্লাসের দিন</span>
        @foreach (\App\Models\Event::TYPES as [$label, , $color])<span><span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:{{ $color }};vertical-align:middle"></span> {{ $label }}</span>@endforeach
    </div>
</div>

<section class="card" style="overflow:hidden">
    <div class="sh"><h2>সামনে যা আছে</h2></div>
    @forelse ($upcoming as $e)
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;padding:12px 24px;border-top:1px solid #DFE5DB;align-items:center">
            <span class="badge {{ $e->typeTone() }}">{{ $e->typeLabel() }}</span>
            <span style="flex:1 1 220px;line-height:1.5"><b>{{ $e->title }}</b><br><span class="mute" style="font-size:14px">{{ $e->dateLabel() }}{{ $e->note ? ' · '.$e->note : '' }}</span></span>
        </div>
    @empty
        <div class="mute" style="padding:0 24px 24px">সামনে কোনো ছুটি বা পরীক্ষা নেই।</div>
    @endforelse
</section>
@endsection
