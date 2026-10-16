@extends('layouts.admin')

@section('title', 'ইমেইল')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ইমেইল</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ইমেইল</h1>
        <p class="lead">ওয়েবসাইট থেকে পাঠানো সব ইমেইল · <a href="{{ route('admin.settings.index') }}#email">ইমেইল সেটিংস</a></p>
    </div>
</div>

<form method="POST" action="{{ route('admin.messages.test') }}" class="card" style="padding:20px 24px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
    @csrf
    <div style="flex:1 1 280px">@include('admin.partials.field', ['name' => 'to', 'label' => 'পরীক্ষামূলক ইমেইল পাঠান', 'type' => 'email', 'value' => auth()->user()->email, 'id' => 'm_to'])</div>
    <button class="btn p">পাঠান</button>
    <span class="badge {{ $enabled ? 'b-g' : 'b-a' }}" style="align-self:center">{{ $enabled ? 'ইমেইল চালু' : 'ইমেইল বন্ধ — সেটিংসে চালু করুন' }}</span>
</form>

<div class="card" style="padding:18px 24px;display:flex;flex-wrap:wrap;gap:10px;align-items:center">
    <b style="color:#0E3A05;margin-right:6px">ইমেইল দেখতে কেমন:</b>
    @foreach (\App\Support\Notifier::EVENTS as $key => [$label])
        <a class="btn sm" href="{{ route('admin.messages.preview', $key) }}" target="_blank">{{ $label }}</a>
    @endforeach
</div>

<nav style="display:flex;flex-wrap:wrap;gap:4px;padding:6px;background:#fff;border:1px solid #DFE5DB;border-radius:14px;width:max-content;max-width:100%">
    <a class="tab {{ ! $status ? 'on' : '' }}" href="{{ route('admin.messages.index') }}">সব</a>
    @foreach (\App\Models\MessageLog::STATUSES as $k => [$label])<a class="tab {{ $status === $k ? 'on' : '' }}" href="{{ route('admin.messages.index', ['status' => $k]) }}">{{ $label }}</a>@endforeach
</nav>

<div class="card" style="overflow:hidden">
    @forelse ($logs as $m)
        <details style="border-bottom:1px solid #DFE5DB">
            <summary style="display:flex;flex-wrap:wrap;gap:6px 16px;padding:14px 24px;cursor:pointer;align-items:center">
                <span class="mute" style="flex:0 0 140px;font-size:14px">{{ bn_date($m->created_at, false) }}, {{ bn($m->created_at->format('h:i A')) }}</span>
                <span style="flex:1 1 240px;min-width:0"><b>{{ $m->student?->name_bn ?? $m->to }}</b> <span class="mute" style="font-size:14px">{{ $m->to }}</span><br><span style="font-size:14px">{{ \App\Support\Notifier::EVENTS[$m->event][0] ?? ($m->event === 'test' ? 'পরীক্ষামূলক' : $m->event) }}</span></span>
                <span class="badge {{ $m->statusTone() }}">{{ $m->statusLabel() }}</span>
            </summary>
            <div style="padding:0 24px 16px;font-size:14px;line-height:1.7">
                <div style="white-space:pre-line;background:#F5F7F3;border-radius:10px;padding:12px 14px">{{ $m->message }}</div>
                @if ($m->response)<div class="mute" style="margin-top:6px">{{ $m->response }}</div>@endif
            </div>
        </details>
    @empty
        <div class="mute" style="padding:24px">এখনো কোনো ইমেইল পাঠানো হয়নি।</div>
    @endforelse
    <div class="pager" style="display:block">{{ $logs->links('partials.pager') }}</div>
</div>
@endsection
