@extends('layouts.admin')

@section('title', 'রিপোর্ট')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>রিপোর্ট</strong>@endsection

@section('content')
<div>
    <h1 class="h1">রিপোর্ট</h1>
    <p class="lead">রিপোর্ট দেখুন ও ডাউনলোড করুন। PDF বাটনে প্রিন্টযোগ্য পেজ খুলবে — সেখান থেকে "PDF হিসেবে সংরক্ষণ" করুন।</p>
</div>

<form method="GET" class="card" style="padding:20px;display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end">
    <div class="ff" style="flex:1 1 220px"><label for="pp">সময়কাল</label>
        <select class="in" id="pp" name="period" onchange="this.form.submit()">@foreach (\App\Http\Controllers\Admin\ReportController::PERIODS as $k => $l)<option value="{{ $k }}" @selected($period === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="ff" style="flex:1 1 220px"><label for="pd">বিভাগ</label>
        <select class="in" id="pd" name="department" onchange="this.form.submit()"><option value="">সব বিভাগ</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected($department === $d->id)>{{ $d->name }}</option>@endforeach</select></div>
</form>

<div class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
    <b style="font-size:18px;color:#0E3A05">মাসিক নতুন ভর্তি</b>
    <div class="chart" role="img" aria-label="গত ৬ মাসের নতুন ভর্তি: {{ $bars->map(fn ($b) => $b['label'].' '.bn($b['n']))->implode(', ') }}">
        @foreach ($bars as $b)
            <div><span style="font-weight:700;font-size:14px">{{ bn($b['n']) }}</span><i style="height:{{ max(1, round($b['n'] / $max * 100)) }}%"></i><small>{{ $b['label'] }}</small></div>
        @endforeach
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr));gap:16px">
    @foreach (\App\Http\Controllers\Admin\ReportController::REPORTS as $key => [$title, $desc])
        @php $q = ['report' => $key, 'period' => $period, 'department' => $department]; @endphp
        <div class="card" style="padding:22px;display:flex;flex-direction:column;gap:14px">
            <span style="display:flex;flex-direction:column;line-height:1.5"><b style="font-size:18px;color:#0E3A05">{{ $title }}</b><span class="mute" style="font-size:15px">{{ $desc }}</span></span>
            <span class="row"><a class="btn" href="{{ route('admin.reports.show', $q) }}" target="_blank">PDF</a><a class="btn" href="{{ route('admin.reports.show', $q + ['format' => 'csv']) }}">Excel</a></span>
        </div>
    @endforeach
</div>
@endsection
