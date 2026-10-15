@extends('layouts.admin')

@section('title', 'আইডি কার্ড — '.$student->name_bn)
@section('crumbs')<a href="{{ route('admin.batches.show', $student->batch) }}">{{ $student->batch->code }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<a href="{{ route('admin.students.show', $student) }}">{{ $student->name_bn }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>আইডি কার্ড</strong>@endsection

@section('content')
<div class="pagehead noprint">
    <div>
        <h1 class="h1">স্টুডেন্ট আইডি কার্ড</h1>
        <p class="lead">কার্ড দেখুন ও প্রিন্ট করুন</p>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('admin.batches.id-cards', $student->batch) }}" target="_blank">ব্যাচের সব কার্ড</a>
        <button class="btn p" onclick="window.print()">@include('partials.icon', ['n' => 'printer', 'size' => 18])প্রিন্ট</button>
    </div>
</div>
<div style="display:flex;flex-wrap:wrap;gap:24px">
    @include('partials.id-card', ['s' => $student])
    <div class="card noprint" style="flex:1 1 300px;padding:24px;align-self:flex-start">
        <h2 style="margin:0 0 12px;font-size:20px;color:#0E3A05">কার্ডের তথ্য</h2>
        <p style="margin:0;color:#55635A;line-height:1.7">ছবি, নাম, আইডি, বিভাগ ও ব্যাচ স্টুডেন্টের প্রোফাইল থেকে আসে।{{ $student->photo ? '' : ' এই স্টুডেন্টের ছবি নেই — ছবি যোগ করতে প্রোফাইলে যান।' }}</p>
        <div style="margin-top:16px"><a class="btn" href="{{ route('admin.students.show', $student) }}#docs">প্রোফাইল খুলুন</a></div>
    </div>
</div>
@endsection
