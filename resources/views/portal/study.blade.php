@extends('layouts.portal')

@section('title', 'অ্যাসাইনমেন্ট ও কুইজ')

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">অ্যাসাইনমেন্ট ও কুইজ</h1>
        <p class="lead">{{ $student->batch->department->course_name ?: $student->batch->department->name }}</p>
    </div>
</div>
@error('quiz')<div class="flash err" role="alert">{{ $message }}</div>@enderror

@if ($blocked)
    <div class="card" style="padding:24px">আপনার ভর্তি সক্রিয় নেই। বিস্তারিত জানতে অফিসে যোগাযোগ করুন।</div>
@else
<section class="card" style="overflow:hidden">
    <div class="sh"><h2>অ্যাসাইনমেন্ট</h2></div>
    @forelse ($assignments as $a)
        @php $sub = $submissions[$a->id] ?? null; @endphp
        <a href="{{ route('portal.assignments.show', $a) }}" style="display:flex;flex-wrap:wrap;gap:8px 16px;padding:16px 24px;border-top:1px solid #DFE5DB;text-decoration:none;color:inherit;align-items:center">
            <span style="flex:1 1 240px;min-width:0;line-height:1.5"><b style="color:#0E3A05">{{ $a->title }}</b><br>
                <span class="mute" style="font-size:14px">পূর্ণ নম্বর {{ bn($a->max_marks) }}{{ $a->due_on ? ' · জমার শেষ তারিখ '.bn_date($a->due_on) : '' }}</span></span>
            @if ($sub?->reviewed_at)
                <span class="badge b-g">নম্বর {{ bn($sub->marks) }}/{{ bn($a->max_marks) }}</span>
            @elseif ($sub)
                <span class="badge b-b">জমা দিয়েছেন</span>
            @elseif ($a->isClosed())
                <span class="badge b-r">জমা দেননি</span>
            @else
                <span class="badge b-a">জমা বাকি</span>
            @endif
        </a>
    @empty
        <div class="mute" style="padding:0 24px 24px">এখনো কোনো অ্যাসাইনমেন্ট নেই।</div>
    @endforelse
</section>

<section class="card" style="overflow:hidden">
    <div class="sh"><h2>কুইজ</h2></div>
    @forelse ($quizzes as $q)
        @php $at = $attempts[$q->id] ?? null; @endphp
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;padding:16px 24px;border-top:1px solid #DFE5DB;align-items:center">
            <span style="flex:1 1 240px;min-width:0;line-height:1.5"><b style="color:#0E3A05">{{ $q->title }}</b><br>
                <span class="mute" style="font-size:14px">{{ bn($q->questions_count) }}টি প্রশ্ন{{ $q->time_limit ? ' · সময় '.bn($q->time_limit).' মিনিট' : '' }}{{ $q->closes_on ? ' · শেষ তারিখ '.bn_date($q->closes_on) : '' }}</span></span>
            @if ($at?->submitted_at)
                <span class="badge b-g">নম্বর {{ bn($at->score) }}/{{ bn($at->total) }}</span>
                <a class="btn sm" href="{{ route('portal.quizzes.show', $q) }}">ফলাফল দেখুন</a>
            @elseif ($at)
                <a class="btn sm p" href="{{ route('portal.quizzes.show', $q) }}">চালিয়ে যান</a>
            @elseif ($q->isClosed())
                <span class="badge b-n">সময় শেষ</span>
            @else
                <a class="btn sm p" href="{{ route('portal.quizzes.show', $q) }}" onclick="return confirm('কুইজ শুরু করবেন?{{ $q->time_limit ? ' শুরু করলেই '.bn($q->time_limit).' মিনিটের ঘড়ি চলবে।' : '' }} একবারই দেওয়া যাবে।')">শুরু করুন</a>
            @endif
        </div>
    @empty
        <div class="mute" style="padding:0 24px 24px">এখনো কোনো কুইজ নেই।</div>
    @endforelse
</section>
@endif
@endsection
