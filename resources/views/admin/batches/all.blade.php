@extends('layouts.admin')

@section('title', 'সব ব্যাচ')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ব্যাচ</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">সব ব্যাচ</h1>
        <p class="lead">নতুন ব্যাচ খুলতে নিচে বিভাগ বেছে নিন।</p>
    </div>
    <div class="row">
        @foreach ($departments as $d)
            <a class="btn sm" href="{{ route('admin.batches.create', $d) }}">+ {{ $d->code }}</a>
        @endforeach
    </div>
</div>
<div class="card" style="overflow:hidden">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;padding:14px 16px">
        <a class="tab {{ ! $filter ? 'on' : '' }}" href="{{ route('admin.batches.all') }}">সব ব্যাচ · {{ bn($counts->sum()) }}</a>
        @foreach (\App\Models\Batch::STATUSES as $key => [$label])
            <a class="tab {{ $filter === $key ? 'on' : '' }}" href="{{ route('admin.batches.all', ['status' => $key]) }}">{{ $label }} · {{ bn($counts[$key] ?? 0) }}</a>
        @endforeach
    </div>
    @include('admin.batches.table', ['batches' => $batches, 'showDept' => true])
</div>
@endsection
