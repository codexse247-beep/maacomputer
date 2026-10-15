@extends('layouts.portal')

@section('title', 'ড্যাশবোর্ড')

@php
    $dayNames = ['শনি', 'রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র'];
    $days = (string) $batch->days;
    $hasDay = fn ($d) => str_contains($days, $d) || ($d === 'বৃহঃ' && str_contains($days, 'বৃহস্পতি'));
@endphp

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">স্বাগতম, {{ $student->name_bn }}</h1>
        <p class="lead">{{ $batch->department->name }} · {{ $batch->label() }} · স্টুডেন্ট আইডি {{ $student->student_code }}</p>
    </div>
    <span class="badge {{ $student->statusTone() }}">{{ $student->statusLabel() }}</span>
</div>

@if ($student->certificate_no)
    <div class="flash ok">অভিনন্দন! আপনার সনদ প্রদান করা হয়েছে — সনদ নং <b>{{ $student->certificate_no }}</b>। যে কেউ <a href="{{ route('verify') }}">সনদ যাচাই</a> পেজে এটি যাচাই করতে পারবেন।</div>
@endif

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr))">
    <div class="card" style="padding:22px;display:flex;flex-direction:column;gap:8px">
        <span style="font-size:14px;font-weight:600;color:#55635A">উপস্থিতি</span>
        @if ($att['percent'] !== null)
            <span style="font-size:36px;line-height:1.3;font-weight:700;color:#176008">{{ bn($att['percent']) }}%</span>
            <span class="bar"><span style="width:{{ $att['percent'] }}%"></span></span>
            <span style="font-size:14px;color:#55635A">{{ bn($att['total']) }}টি ক্লাসের মধ্যে {{ bn($att['present']) }}টিতে উপস্থিত</span>
        @else
            <span style="font-size:36px;line-height:1.3;font-weight:700;color:#0E3A05">—</span>
            <span style="font-size:14px;color:#55635A">এখনো উপস্থিতি নেওয়া হয়নি</span>
        @endif
    </div>
    <div class="card" style="padding:22px;display:flex;flex-direction:column;gap:8px">
        <span style="font-size:14px;font-weight:600;color:#55635A">কোর্স অগ্রগতি</span>
        <span style="font-size:36px;line-height:1.3;font-weight:700;color:#0E3A05">{{ $progress === null ? '—' : bn($progress).'%' }}</span>
        @if ($progress !== null)<span class="bar"><span style="width:{{ $progress }}%"></span></span>@endif
        <span style="font-size:14px;color:#55635A">কোর্স শেষ হবে {{ bn_date($batch->end_date) }}</span>
    </div>
    <div id="fees" class="card" style="padding:22px;display:flex;flex-direction:column;gap:8px;{{ $student->totalDue() ? 'background:#FFFBF2;border-color:#F0DDB0' : '' }}">
        <span style="font-size:14px;font-weight:600;color:#55635A">ফি বকেয়া</span>
        <span style="font-size:36px;line-height:1.3;font-weight:700;color:{{ $student->totalDue() ? '#B00E28' : '#176008' }}">{{ taka($student->totalDue()) }}</span>
        <span style="font-size:14px;color:#55635A">মোট {{ taka($student->totalPayable()) }} · পরিশোধিত {{ taka($student->totalPaid()) }}</span>
        @if ($student->totalDue() && $student->next_installment_date)
            <span style="font-size:14px;color:#8A5300;font-weight:600">পরবর্তী কিস্তি: {{ $student->next_installment_amount ? taka($student->next_installment_amount).' · ' : '' }}{{ bn_date($student->next_installment_date) }}</span>
        @endif
    </div>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <div style="flex:999 1 560px;min-width:0;display:flex;flex-direction:column;gap:24px">

        <section id="course" class="card">
            <div class="sh"><h2>ক্লাস রুটিন</h2></div>
            <div style="padding:0 24px 24px;display:flex;flex-direction:column;gap:18px">
                <div style="padding:16px 18px;border-radius:14px;background:#F1F7EE;display:flex;flex-direction:column;line-height:1.5">
                    <span style="font-size:13px;font-weight:600;color:#55635A">{{ $batch->department->course_name ?: $batch->department->name }}</span>
                    <b style="font-size:19px;color:#0E3A05">{{ $batch->days ?: 'রুটিন শীঘ্রই জানানো হবে' }}</b>
                    <span style="font-size:14px;color:#55635A">{{ collect([$batch->class_time, $batch->room, $batch->teacher ? 'শিক্ষক: '.$batch->teacher : null])->filter()->implode(' · ') }}</span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px">
                    @foreach ($dayNames as $d)
                        <div class="day {{ $hasDay($d) ? 'on' : '' }}"><span>{{ $d }}</span><b>{{ $hasDay($d) ? 'ক্লাস' : ($d === 'শুক্র' ? 'বন্ধ' : '—') }}</b></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="results" class="card" style="overflow:hidden">
            <div class="sh"><h2>মূল্যায়ন ও ফলাফল</h2></div>
            <div class="scroll">
                <table class="tbl" style="min-width:560px">
                    <thead><tr><th class="th">মূল্যায়ন</th><th class="th">তারিখ</th><th class="th">নম্বর</th><th class="th">গ্রেড</th><th class="th">অবস্থা</th></tr></thead>
                    <tbody>
                    @forelse ($student->assessments as $m)
                        @php [$g, $gt] = $m->grade(); @endphp
                        <tr><td class="td"><b>{{ $m->title }}</b>@if ($m->remark)<br><span class="mute" style="font-size:14px">{{ $m->remark }}</span>@endif</td><td class="td">{{ bn_date($m->held_on) }}</td><td class="td"><b>{{ $m->scoreText() }}</b></td><td class="td"><span class="badge {{ $gt }}">{{ $g }}</span></td><td class="td"><span class="badge {{ $m->statusTone() }}">{{ $m->statusLabel() }}</span></td></tr>
                    @empty
                        <tr><td class="td mute" colspan="5">এখনো কোনো ফলাফল প্রকাশ হয়নি।</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card" style="overflow:hidden">
            <div class="sh"><h2>পেমেন্টের ইতিহাস</h2></div>
            <div class="scroll">
                <table class="tbl" style="min-width:560px">
                    <thead><tr><th class="th">রসিদ নং</th><th class="th">তারিখ</th><th class="th">খাত</th><th class="th">পরিমাণ</th><th class="th"></th></tr></thead>
                    <tbody>
                    @forelse ($student->payments as $p)
                        <tr><td class="td"><b>{{ $p->receipt_no }}</b></td><td class="td">{{ bn_date($p->paid_on) }}</td><td class="td">{{ $p->item }}</td><td class="td"><b>{{ taka($p->amount) }}</b></td><td class="td" style="text-align:right"><a class="el" href="{{ route('portal.receipt', $p) }}" target="_blank">রসিদ</a></td></tr>
                    @empty
                        <tr><td class="td mute" colspan="5">এখনো কোনো পেমেন্ট নেই।</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="notices" class="card">
            <div class="sh"><h2>নোটিশ</h2></div>
            <div style="padding:0 24px 24px;display:flex;flex-direction:column">
                @forelse ($notices as $n)
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px 16px;padding:14px 0;border-top:1px solid #DFE5DB">
                        <span style="min-width:96px;font-size:14px;font-weight:600;color:#55635A">{{ bn_date($n->published_on, false) }}</span>
                        <span style="flex:1 1 240px;display:flex;flex-direction:column"><span style="font-weight:600;color:#0E3A05">{{ $n->title }}</span>@if ($n->body)<span style="font-size:14px;color:#55635A;white-space:pre-line">{{ $n->body }}</span>@endif</span>
                        <span class="badge {{ $n->tagTone() }}">{{ $n->tagLabel() }}</span>
                    </div>
                @empty
                    <p class="mute" style="margin:0">কোনো নোটিশ নেই।</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:24px">
        <div class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
            <h2 style="margin:0;font-size:19px;font-weight:700;color:#0E3A05">আমার তথ্য</h2>
            <div class="fld"><span>পিতার নাম</span><b>{{ $student->father_name }}</b></div>
            <div class="fld"><span>মাতার নাম</span><b>{{ $student->mother_name }}</b></div>
            <div class="fld"><span>মোবাইল</span><b>{{ bn($student->phone) }}</b></div>
            <div class="fld"><span>ভর্তির তারিখ</span><b>{{ bn_date($student->admission_date) }}</b></div>
            <div class="fld"><span>কোর্স</span><b>{{ $batch->department->course_name ?: $batch->department->name }}</b></div>
            <a class="btn" href="{{ route('portal.profile') }}">সম্পূর্ণ প্রোফাইল ও পাসওয়ার্ড</a>
        </div>

        <div class="card" style="padding:24px;display:flex;flex-direction:column;gap:12px">
            <h2 style="margin:0 0 4px;font-size:19px;font-weight:700;color:#0E3A05">ডাউনলোড</h2>
            <a class="dl" href="{{ route('portal.id-card') }}" target="_blank">আইডি কার্ড @include('partials.icon', ['n' => 'download', 'size' => 18])</a>
            @foreach ($student->payments->take(4) as $p)
                <a class="dl" href="{{ route('portal.receipt', $p) }}" target="_blank">রসিদ {{ $p->receipt_no }} @include('partials.icon', ['n' => 'download', 'size' => 18])</a>
            @endforeach
        </div>
    </aside>
</div>
@endsection
