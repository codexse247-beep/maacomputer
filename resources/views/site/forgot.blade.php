@extends('layouts.site')

@section('title', __('পাসওয়ার্ড পুনরুদ্ধার'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('পাসওয়ার্ড'), 'title' => __('পাসওয়ার্ড ভুলে গেছেন?'), 'lead' => __('স্টুডেন্ট আইডি ও মোবাইল নম্বর দিন। যাচাই করে অফিস থেকে আপনাকে ফোনে নতুন পাসওয়ার্ড জানানো হবে।')])

<section style="padding:56px 0 88px">
<div class="wrap">
    <form method="POST" action="{{ route('forgot.store') }}" class="pcard" style="max-width:520px;margin:0 auto;display:flex;flex-direction:column;gap:18px">
        @csrf
        @include('partials.site-flash')
        @include('admin.partials.field', ['name' => 'student_code', 'label' => __('স্টুডেন্ট আইডি'), 'req' => true, 'value' => '', 'ph' => 'MCT-2607-004', 'id' => 'fi'])
        @include('admin.partials.field', ['name' => 'phone', 'label' => __('মোবাইল নম্বর'), 'req' => true, 'value' => '', 'type' => 'tel', 'ph' => '01XXXXXXXXX', 'id' => 'fp'])
        <button class="btn p" type="submit">{{ __('অনুরোধ পাঠান') }}</button>
        <a href="{{ route('login') }}" style="font-size:15px">{{ __('লগইনে ফিরে যান') }}</a>
    </form>
</div>
</section>
@endsection
