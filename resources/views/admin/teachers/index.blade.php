@extends('layouts.admin')

@section('title', 'শিক্ষক')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>শিক্ষক</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">শিক্ষক</h1>
        <p class="lead">সব শিক্ষকের তালিকা। "ওয়েবসাইটে দেখান" চালু থাকলে শিক্ষকবৃন্দ পেজে দেখাবে।</p>
    </div>
    <a class="btn p" href="#new" onclick="document.getElementById('new').open=true">@include('partials.icon', ['n' => 'plus', 'size' => 18, 'w' => 2.4])নতুন শিক্ষক</a>
</div>

<div class="card" style="overflow:hidden">
    <div class="scroll">
        <table class="tbl" style="min-width:820px">
            <thead><tr><th class="th">নাম</th><th class="th">বিভাগ</th><th class="th">ব্যাচ</th><th class="th">মোবাইল</th><th class="th">অবস্থা</th><th class="th"></th></tr></thead>
            <tbody>
            @forelse ($teachers as $t)
                <tr>
                    <td class="td"><span style="display:flex;align-items:center;gap:12px">@if ($t->photoUrl())<img class="av" src="{{ $t->photoUrl() }}" alt="">@else<span class="av">{{ initial($t->name) }}</span>@endif<span style="display:flex;flex-direction:column;line-height:1.4"><b>{{ $t->name }}</b>@if ($t->subject)<span class="mute" style="font-size:13px">{{ $t->subject }}</span>@endif</span></span></td>
                    <td class="td">{{ $t->department?->name ?? '—' }}</td>
                    <td class="td">@forelse ($t->batches as $b)<a href="{{ route('admin.batches.show', $b) }}">{{ $b->code }}</a>{{ $loop->last ? '' : ', ' }}@empty<span class="mute">—</span>@endforelse</td>
                    <td class="td">{{ $t->phone ? bn($t->phone) : '—' }}</td>
                    <td class="td"><span class="badge {{ $t->active ? 'b-g' : 'b-n' }}">{{ $t->active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}</span>@if ($t->show_on_site) <span class="badge b-b">ওয়েবসাইটে</span>@endif</td>
                    <td class="td" style="text-align:right"><a class="btn sm" href="{{ route('admin.teachers.index', ['edit' => $t->id]) }}#edit">সম্পাদনা</a></td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="6">এখনো কোনো শিক্ষক যোগ করা হয়নি।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($editing && ($t = $teachers->firstWhere('id', $editing)))
<form id="edit" method="POST" action="{{ route('admin.teachers.update', $t) }}" enctype="multipart/form-data" class="card">
    @csrf @method('PUT')
    <div class="sh"><h2>সম্পাদনা — {{ $t->name }}</h2></div>
    @include('admin.teachers.fields', ['t' => $t])
    <div style="padding:0 24px 24px;display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px">
        <div class="row"><a class="btn" href="{{ route('admin.teachers.index') }}">বাতিল</a><button class="btn p">সংরক্ষণ</button></div>
    </div>
</form>
<form method="POST" action="{{ route('admin.teachers.destroy', $t) }}" onsubmit="return confirm('{{ $t->name }} কে মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="btn d">শিক্ষক মুছুন</button></form>
@endif

<details id="new" class="card" @if ($errors->any() && ! $editing) open @endif>
    <summary style="cursor:pointer;padding:20px 24px;font-size:19px;font-weight:700;color:#0E3A05">+ নতুন শিক্ষক যোগ করুন</summary>
    <form method="POST" action="{{ route('admin.teachers.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.teachers.fields', ['t' => new \App\Models\Teacher(['active' => true, 'show_on_site' => true])])
        <div style="padding:0 24px 24px"><button class="btn p">শিক্ষক যোগ করুন</button></div>
    </form>
</details>
@endsection
