@extends('layouts.site')

@section('title', 'পেজটি পাওয়া যায়নি')

@section('content')
@include('partials.page-hero', ['eyebrow' => '৪০৪', 'title' => 'পেজটি খুঁজে পাওয়া যায়নি', 'lead' => 'লিংকটি ভুল হতে পারে অথবা পেজটি সরানো হয়েছে।'])
<section style="padding:48px 0 120px">
<div class="wrap">
    <div style="display:flex;flex-wrap:wrap;gap:12px"><a class="btn p" href="{{ route('home') }}">হোমে ফিরুন</a><a class="btn" href="{{ route('contact') }}">যোগাযোগ করুন</a></div>
</div>
</section>
@endsection
