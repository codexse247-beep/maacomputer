@extends('layouts.admin')

@section('title', 'উপস্থিতি')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>উপস্থিতি</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">উপস্থিতি</h1>
        <p class="lead">ব্যাচ ও তারিখ বেছে হাজিরা নিন</p>
    </div>
    @if ($batch)<a class="btn" href="{{ route('admin.attendance.history', $batch) }}">ইতিহাস দেখুন</a>@endif
</div>

<form method="GET" class="card" style="padding:20px;display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end">
    <div class="ff" style="flex:1 1 220px">
        <label for="ab">ব্যাচ</label>
        <select class="in" id="ab" name="batch" onchange="this.form.submit()">
            @foreach ($batches as $b)<option value="{{ $b->id }}" @selected($batch && $batch->id === $b->id)>{{ $b->code }} · {{ $b->name }}</option>@endforeach
        </select>
    </div>
    <div class="ff" style="flex:1 1 220px">
        <label for="ad">তারিখ</label>
        <input class="in" id="ad" type="date" name="date" value="{{ $date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" onchange="this.form.submit()">
    </div>
    <noscript><button class="btn">দেখুন</button></noscript>
</form>

@if (! $batch)
    <div class="card mute" style="padding:24px">কোনো চলমান ব্যাচ নেই। <a href="{{ route('admin.batches.all') }}">ব্যাচ খুলুন</a></div>
@else
<div class="stats">
    <div class="card statc"><span class="k">মোট স্টুডেন্ট</span><span class="v">{{ bn($students->count()) }} জন</span><span class="s">{{ $batch->code }}</span></div>
    <div class="card statc"><span class="k">গড় উপস্থিতি</span><span class="v">{{ $avg === null ? '—' : bn($avg).'%' }}</span><span class="s">গত ৩০ দিন</span></div>
    <div class="card statc"><span class="k">{{ $date->isToday() ? 'আজ' : bn_date($date, false) }} অনুপস্থিত</span><span class="v" style="{{ $absentToday ? 'color:#B00E28' : '' }}">{{ $absentToday === null ? '—' : bn($absentToday).' জন' }}</span><span class="s">{{ $absentToday === null ? 'হাজিরা নেওয়ার পর দেখা যাবে' : 'সংরক্ষিত হাজিরা অনুযায়ী' }}</span></div>
</div>

<form method="POST" action="{{ route('admin.attendance.store', $batch) }}" class="card" style="overflow:hidden">
    @csrf
    <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
    <div class="sh"><h2>{{ bn_weekday($date) }}, {{ bn_date($date) }}</h2><span class="badge {{ $marked->isEmpty() ? 'b-a' : 'b-g' }}">{{ $marked->isEmpty() ? 'এখনো নেওয়া হয়নি' : 'নেওয়া হয়েছে' }}</span></div>
    <div class="scroll">
        <table class="tbl" style="min-width:640px">
            <thead><tr><th class="th">রোল</th><th class="th">স্টুডেন্ট</th><th class="th">হাজিরা</th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                @php $current = $marked[$s->id] ?? 'present'; @endphp
                <tr>
                    <td class="td" style="height:64px"><b>{{ bn(str_pad($s->roll, 2, '0', STR_PAD_LEFT)) }}</b></td>
                    <td class="td" style="height:64px"><b>{{ $s->name_bn }}</b><br><span class="mute" style="font-size:14px">{{ $s->student_code }}</span></td>
                    <td class="td" style="height:64px">
                        <fieldset style="border:0;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:4px">
                            <legend class="sr">{{ $s->name_bn }} এর হাজিরা</legend>
                            @foreach (\App\Models\Attendance::STATUSES as $key => $label)
                                <label class="rd"><input type="radio" name="status[{{ $s->id }}]" value="{{ $key }}" @checked($current === $key)>{{ $label }}</label>
                            @endforeach
                        </fieldset>
                    </td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="3">এই ব্যাচে কোনো সক্রিয় স্টুডেন্ট নেই।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px;padding:16px;border-top:1px solid #DFE5DB">
        <button type="button" class="btn" onclick="document.querySelectorAll('input[type=radio][value=present]').forEach(r => r.checked = true)">সবাইকে উপস্থিত করুন</button>
        <button class="btn p">হাজিরা সংরক্ষণ</button>
    </div>
</form>
@endif
@endsection
