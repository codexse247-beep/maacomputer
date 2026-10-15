@extends('layouts.admin')

@section('title', 'রসিদ '.$payment->receipt_no)
@section('crumbs')<a href="{{ route('admin.payments.index') }}">ফি ও পেমেন্ট</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<a href="{{ route('admin.students.show', $student) }}">{{ $student->name_bn }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>রসিদ {{ $payment->receipt_no }}</strong>@endsection

@section('content')
<div class="pagehead noprint">
    <div>
        <h1 class="h1">পেমেন্ট রসিদ</h1>
        <p class="lead">প্রিন্ট করার জন্য প্রস্তুত। PDF চাইলে প্রিন্ট উইন্ডোতে "Save as PDF" বেছে নিন।</p>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('admin.students.show', $student) }}#fees">প্রোফাইলে ফিরুন</a>
        <button class="btn p" onclick="window.print()">@include('partials.icon', ['n' => 'printer', 'size' => 18])প্রিন্ট</button>
    </div>
</div>
@include('partials.receipt-card')
@endsection
