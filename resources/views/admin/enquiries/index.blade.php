@extends('layouts.admin')

@section('title', 'অনলাইন আবেদন')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>অনলাইন আবেদন ও মেসেজ</strong>@endsection

@section('content')
<div>
    <h1 class="h1">অনলাইন আবেদন ও মেসেজ</h1>
    <p class="lead">ওয়েবসাইট থেকে আসা ভর্তির আবেদন, মেসেজ ও পাসওয়ার্ড রিসেটের অনুরোধ। ফোন করে কাজ শেষ হলে "সম্পন্ন" চাপুন।</p>
</div>

<div class="card" style="overflow:hidden">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px">
        <div class="row" style="gap:6px">
            <a class="tab {{ ! $type ? 'on' : '' }}" href="{{ route('admin.enquiries.index', ['status' => $status]) }}">সব · {{ bn($counts->sum()) }}</a>
            @foreach (\App\Models\Enquiry::TYPES as $key => [$label])
                <a class="tab {{ $type === $key ? 'on' : '' }}" href="{{ route('admin.enquiries.index', ['type' => $key, 'status' => $status]) }}">{{ $label }} · {{ bn($counts[$key] ?? 0) }}</a>
            @endforeach
        </div>
        <div class="row" style="gap:6px">
            <a class="tab {{ $status === 'new' ? 'on' : '' }}" href="{{ route('admin.enquiries.index', ['type' => $type, 'status' => 'new']) }}">নতুন</a>
            <a class="tab {{ $status === 'done' ? 'on' : '' }}" href="{{ route('admin.enquiries.index', ['type' => $type, 'status' => 'done']) }}">সম্পন্ন</a>
            <a class="tab {{ $status === 'all' ? 'on' : '' }}" href="{{ route('admin.enquiries.index', ['type' => $type, 'status' => 'all']) }}">সব</a>
        </div>
    </div>
    @forelse ($enquiries as $e)
        <div style="display:flex;flex-wrap:wrap;gap:12px 24px;padding:18px 24px;border-top:1px solid #DFE5DB;{{ $e->status === 'new' ? '' : 'opacity:.75' }}">
            <div style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:6px;line-height:1.5">
                <div class="row" style="gap:8px"><span class="badge {{ $e->typeTone() }}">{{ $e->typeLabel() }}</span><b style="font-size:17px;color:#0E3A05">{{ $e->name }}</b>@if ($e->student_code)<span class="mute">· {{ $e->student_code }}</span>@endif</div>
                <div style="font-size:15px"><a href="tel:{{ $e->phone }}" style="font-weight:700">{{ bn($e->phone) }}</a>
                    @if ($e->father_name) · পিতা: {{ $e->father_name }}@endif
                    @if ($e->department) · <b>{{ $e->department->name }}</b>@endif
                    @if ($e->preferred_time) · {{ $e->preferred_time }}@endif
                    @if ($e->education) · {{ $e->education }}@endif
                    @if ($e->dob) · জন্ম: {{ bn_date($e->dob) }}@endif
                    @if ($e->email) · {{ $e->email }}@endif
                </div>
                @if ($e->subject)<div style="font-weight:600">{{ $e->subject }}</div>@endif
                @if ($e->message)<div class="mute" style="white-space:pre-line;font-size:15px">{{ $e->message }}</div>@endif
                <div class="mute" style="font-size:13px">{{ bn_date($e->created_at) }}, {{ bn($e->created_at->format('h:i')) }}{{ $e->handler ? ' · সম্পন্ন করেছেন: '.$e->handler->name : '' }}</div>
            </div>
            <div class="row" style="align-self:center">
                @if ($e->type === 'admission' && $e->status === 'new')
                    <a class="btn sm p" href="{{ route('admin.students.create', ['enquiry' => $e->id]) }}">ভর্তি করুন</a>
                @endif
                @if ($e->type === 'password' && ($s = \App\Models\Student::where('student_code', $e->student_code)->first()))
                    <a class="btn sm" href="{{ route('admin.students.show', $s) }}">প্রোফাইল খুলুন</a>
                @endif
                <form method="POST" action="{{ route('admin.enquiries.update', $e) }}">@csrf @method('PATCH')
                    <button class="btn sm {{ $e->status === 'new' ? 'p' : '' }}" name="status" value="{{ $e->status === 'new' ? 'done' : 'new' }}">{{ $e->status === 'new' ? 'সম্পন্ন' : 'আবার খুলুন' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.enquiries.destroy', $e) }}" onsubmit="return confirm('মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>
            </div>
        </div>
    @empty
        <div class="mute" style="padding:24px;border-top:1px solid #DFE5DB">এখানে কিছু নেই।</div>
    @endforelse
    <div class="pager" style="display:block">{{ $enquiries->links('partials.pager') }}</div>
</div>
@endsection
