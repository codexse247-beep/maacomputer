@extends('layouts.admin')

@section('title', 'অ্যাক্টিভিটি লগ')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>অ্যাক্টিভিটি লগ</strong>@endsection

@section('content')
<div>
    <h1 class="h1">অ্যাক্টিভিটি লগ</h1>
    <p class="lead">কে কখন কী যোগ, পরিবর্তন বা মুছেছে</p>
</div>

<form method="GET" class="card" style="padding:18px 24px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
    <div class="ff" style="flex:1 1 180px"><label for="f_user">ব্যবহারকারী</label>
        <select class="in" id="f_user" name="user"><option value="">সবাই</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="ff" style="flex:1 1 160px"><label for="f_type">বিষয়</label>
        <select class="in" id="f_type" name="type"><option value="">সব</option>@foreach (\App\Models\ActivityLog::SUBJECTS as $k => $l)<option value="{{ $k }}" @selected($type === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="ff" style="flex:1 1 160px"><label for="f_action">কাজ</label>
        <select class="in" id="f_action" name="action"><option value="">সব</option>@foreach (\App\Models\ActivityLog::ACTIONS as $k => $a)<option value="{{ $k }}" @selected($action === $k)>{{ $a[0] }}</option>@endforeach</select></div>
    <div class="ff" style="flex:1 1 160px"><label for="f_date">তারিখ</label><input class="in" type="date" id="f_date" name="date" value="{{ request('date') }}"></div>
    <button class="btn p">দেখুন</button>
    @if (request()->hasAny(['user', 'type', 'action', 'date']))<a class="btn" href="{{ route('admin.activity.index') }}">সব দেখুন</a>@endif
</form>

<div class="card" style="overflow:hidden">
    @forelse ($logs as $log)
        <div style="display:flex;flex-wrap:wrap;gap:6px 16px;padding:14px 24px;border-bottom:1px solid #DFE5DB;align-items:flex-start">
            <span class="mute" style="flex:0 0 150px;font-size:14px">{{ bn_date($log->created_at) }}<br>{{ bn($log->created_at->format('h:i A')) }}</span>
            <span style="flex:1 1 320px;min-width:0;line-height:1.6">
                <b>{{ $log->actorName() }}</b> <span class="badge {{ $log->actionTone() }}" style="height:24px">{{ $log->actionLabel() }}</span>
                {{ $log->subjectLabel() }}: <b>{{ $log->label }}</b>
                @if ($log->diff)
                    <span style="display:block;font-size:13px;color:#55635A">
                        @foreach ($log->diff as $field => [$old, $new])
                            <span style="display:inline-block;margin-right:12px">{{ $field }}: <s>{{ $old ?? '—' }}</s> → {{ $new ?? '—' }}</span>
                        @endforeach
                    </span>
                @endif
            </span>
            <span class="mute" style="font-size:12px">{{ $log->ip }}</span>
        </div>
    @empty
        <div class="mute" style="padding:24px">কোনো রেকর্ড নেই।</div>
    @endforelse
    <div class="pager" style="display:block">{{ $logs->links('partials.pager') }}</div>
</div>
@endsection
