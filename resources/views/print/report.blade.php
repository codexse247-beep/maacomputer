@extends('print.layout')

@section('title', $title)

@push('head')
<style>
@media print{@page{size:A4 landscape;margin:10mm}}
.rep{width:100%;border-collapse:collapse;font-size:13px}
.rep th,.rep td{border:1px solid #C9D2C5;padding:6px 8px;text-align:left;vertical-align:top}
.rep th{background:#F1F7EE}
.rep tfoot td{font-weight:700;background:#F5F7F3}
</style>
@endpush

@section('content')
<div class="printpage" style="max-width:1200px;display:flex;flex-direction:column;gap:16px">
    <div style="display:flex;align-items:center;gap:14px;border-bottom:2px solid #176008;padding-bottom:12px">
        <img src="{{ logo_url() }}" alt="" style="width:52px;height:52px;border-radius:50%">
        <div style="line-height:1.4"><b style="font-size:18px;color:#0E3A05">{{ setting('name') }}</b><br><b>{{ $title }}</b>@if ($subtitle) · <span class="mute">{{ $subtitle }}</span>@endif<br><span class="mute" style="font-size:13px">তৈরির তারিখ: {{ bn_date(now()) }} · মোট {{ bn(count($rows)) }}টি সারি</span></div>
    </div>
    <div class="scroll">
        <table class="rep">
            <thead><tr><th>#</th>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse ($rows as $i => $row)
                <tr><td>{{ bn($i + 1) }}</td>@foreach ($row as $cell)<td>{{ is_int($cell) ? bn(number_format($cell)) : $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) + 1 }}">কোনো তথ্য নেই।</td></tr>
            @endforelse
            </tbody>
            @if ($total)
                <tfoot><tr><td></td>@foreach ($total as $cell)<td>{{ is_int($cell) ? bn(number_format($cell)) : $cell }}</td>@endforeach</tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
