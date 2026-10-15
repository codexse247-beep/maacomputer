@extends('layouts.admin')

@section('title', 'ফি ও পেমেন্ট')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ফি ও পেমেন্ট</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ফি ও পেমেন্ট</h1>
        <p class="lead">মাসিক আদায় ও বকেয়ার হিসাব</p>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('admin.reports.show', ['report' => 'fees', 'format' => 'csv']) }}">এক্সপোর্ট</a>
        <a class="btn p" href="#rows" onclick="document.getElementById('fq').focus()">নতুন পেমেন্ট</a>
    </div>
</div>

<div class="stats">
    <div class="card statc"><span class="k">এই মাসে আদায়</span><span class="v">{{ taka($thisMonth) }}</span><span class="s">@if ($change === null)গত মাসের তথ্য নেই@else গত মাসের এই সময়ের চেয়ে {{ bn(abs($change)) }}% {{ $change >= 0 ? 'বেশি' : 'কম' }}@endif</span></div>
    <div class="card statc"><span class="k">মোট বকেয়া</span><span class="v" style="color:#B00E28">{{ taka($totalDue) }}</span><span class="s">{{ bn($dueCount) }} জন স্টুডেন্টের</span></div>
    <div class="card statc"><span class="k">আজকের আদায়</span><span class="v" style="color:#176008">{{ taka($today->total) }}</span><span class="s">{{ bn($today->n) }}টি পেমেন্ট</span></div>
</div>

<form method="GET" class="card" style="padding:20px;display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end">
    <div class="ff" style="flex:1 1 200px"><label for="fd">বিভাগ</label>
        <select class="in" id="fd" name="department" onchange="this.form.submit()"><option value="">সব বিভাগ</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="ff" style="flex:1 1 200px"><label for="fb">ব্যাচ</label>
        <select class="in" id="fb" name="batch" onchange="this.form.submit()"><option value="">সব ব্যাচ</option>@foreach ($batches as $b)<option value="{{ $b->id }}" @selected(request('batch') == $b->id)>{{ $b->code }}</option>@endforeach</select></div>
    <div class="ff" style="flex:1 1 200px"><label for="fs">অবস্থা</label>
        <select class="in" id="fs" name="fee" onchange="this.form.submit()"><option value="">সব</option>@foreach (\App\Models\Student::FEE_STATES as $k => [$l])<option value="{{ $k }}" @selected(request('fee') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="ff" style="flex:1 1 200px"><label for="fq">স্টুডেন্ট খুঁজুন</label>
        <input class="in" id="fq" type="search" placeholder="নাম বা আইডি লিখুন" oninput="const q=this.value.trim().toLowerCase();document.querySelectorAll('#rows tbody tr[data-q]').forEach(r=>r.hidden=q&&!r.dataset.q.includes(q))"></div>
</form>

<div class="card" style="overflow:hidden" id="rows">
    <div class="scroll">
        <table class="tbl" style="min-width:900px">
            <thead><tr><th class="th">স্টুডেন্ট</th><th class="th">ব্যাচ</th><th class="th">মোট ফি</th><th class="th">পরিশোধিত</th><th class="th">বকেয়া</th><th class="th">পরবর্তী কিস্তি</th><th class="th">অবস্থা</th><th class="th"></th></tr></thead>
            <tbody>
            @forelse ($rows as $s)
                @php $late = $s->totalDue() && $s->next_installment_date && $s->next_installment_date->isPast(); @endphp
                <tr data-q="{{ mb_strtolower($s->name_bn.' '.$s->name_en.' '.$s->student_code.' '.$s->phone) }}">
                    <td class="td"><a href="{{ route('admin.students.show', $s) }}" style="text-decoration:none;display:flex;flex-direction:column;line-height:1.4"><b style="color:#0E3A05;font-size:16px">{{ $s->name_bn }}</b><span class="mute" style="font-size:14px">{{ $s->student_code }}</span></a></td>
                    <td class="td">{{ $s->batch->code }}</td>
                    <td class="td">{{ taka($s->totalPayable()) }}</td>
                    <td class="td">{{ taka($s->totalPaid()) }}</td>
                    <td class="td"><b style="{{ $s->totalDue() ? 'color:#B00E28' : '' }}">{{ taka($s->totalDue()) }}</b></td>
                    <td class="td">@if ($s->totalDue() && $s->next_installment_date)<span class="badge {{ $late ? 'b-r' : 'b-a' }}">{{ bn_date($s->next_installment_date, false) }}{{ $late ? ' · পেরিয়েছে' : '' }}</span>@else<span class="mute">—</span>@endif</td>
                    <td class="td"><span class="badge {{ $s->feeTone() }}">{{ $s->feeLabel() }}</span></td>
                    <td class="td" style="text-align:right">@if ($s->totalDue())<a class="btn sm" href="{{ route('admin.students.show', $s) }}#fees">পেমেন্ট গ্রহণ</a>@endif</td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="8">কোনো স্টুডেন্ট পাওয়া যায়নি।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="overflow:hidden">
    <div class="sh"><h2>সাম্প্রতিক পেমেন্ট</h2></div>
    <div class="scroll">
        <table class="tbl" style="min-width:760px">
            <thead><tr><th class="th">রসিদ নং</th><th class="th">তারিখ</th><th class="th">স্টুডেন্ট</th><th class="th">খাত</th><th class="th">পরিমাণ</th><th class="th">মাধ্যম</th><th class="th"></th></tr></thead>
            <tbody>
            @forelse ($recent as $p)
                <tr><td class="td"><b>{{ $p->receipt_no }}</b></td><td class="td">{{ bn_date($p->paid_on) }}</td><td class="td"><a href="{{ route('admin.students.show', $p->student) }}">{{ $p->student->name_bn }}</a></td><td class="td">{{ $p->item }}</td><td class="td"><b>{{ taka($p->amount) }}</b></td><td class="td">{{ $p->method }}</td><td class="td" style="text-align:right"><a class="el" href="{{ route('admin.payments.receipt', $p) }}">রসিদ</a></td></tr>
            @empty
                <tr><td class="td mute" colspan="7">এখনো কোনো পেমেন্ট নেই।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
