@extends('layouts.admin')

@section('title', 'উপস্থিতির ইতিহাস — '.$batch->code)
@section('crumbs')<a href="{{ route('admin.attendance.index', ['batch' => $batch->id]) }}">উপস্থিতি</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $batch->code }} · ইতিহাস</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">উপস্থিতির ইতিহাস — {{ $batch->code }}</h1>
        <p class="lead">সর্বশেষ {{ bn($dates->count()) }}টি ক্লাস দিন · উ = উপস্থিত, অ = অনুপস্থিত, ছু = ছুটি</p>
    </div>
    <a class="btn" href="{{ route('admin.reports.show', ['report' => 'attendance', 'batch' => $batch->id, 'format' => 'csv']) }}">এক্সপোর্ট</a>
</div>
<div class="card" style="overflow:hidden">
    <div class="scroll">
        <table class="tbl" style="min-width:{{ 360 + $dates->count() * 46 }}px">
            <thead><tr><th class="th" style="position:sticky;left:0;z-index:1">স্টুডেন্ট</th><th class="th">হার</th>
                @foreach ($dates as $d)<th class="th" style="padding:0 6px;text-align:center;font-size:12px">{{ bn(\Illuminate\Support\Carbon::parse($d)->format('d/m')) }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse ($students as $s)
                @php $att = $s->attendanceStats(); @endphp
                <tr>
                    <td class="td" style="position:sticky;left:0;background:#fff;white-space:nowrap"><a href="{{ route('admin.students.show', $s) }}" style="text-decoration:none"><b style="color:#0E3A05">{{ $s->name_bn }}</b></a><br><span class="mute" style="font-size:13px">রোল {{ bn($s->roll) }}</span></td>
                    <td class="td"><b>{{ $att['percent'] === null ? '—' : bn($att['percent']).'%' }}</b></td>
                    @foreach ($dates as $d)
                        @php $st = $grid[$s->id][$d]->status ?? null; @endphp
                        <td class="td" style="padding:0 6px;text-align:center;font-weight:700;color:{{ ['present' => '#176008', 'absent' => '#B00E28', 'leave' => '#8A5300'][$st] ?? '#9AA59D' }}">{{ ['present' => 'উ', 'absent' => 'অ', 'leave' => 'ছু'][$st] ?? '·' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="td mute" colspan="{{ 2 + $dates->count() }}">কোনো স্টুডেন্ট নেই।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
