@extends('layouts.site')

@section('title', __('পেজটি পাওয়া যায়নি'))

@section('content')
@include('partials.page-hero', ['eyebrow' => bn(404), 'title' => __('পেজটি খুঁজে পাওয়া যায়নি'), 'lead' => __('লিংকটি ভুল হতে পারে অথবা পেজটি সরানো হয়েছে।')])
<section style="padding:48px 0 120px">
<div class="wrap">
    <div style="display:flex;flex-wrap:wrap;gap:12px"><a class="btn p" href="{{ route('home') }}">{{ __('হোমে ফিরুন') }}</a><a class="btn" href="{{ route('contact') }}">{{ __('যোগাযোগ করুন') }}</a></div>
</div>
</section>
@endsection
