@extends('layouts.site')

@section('title', __('সনদ যাচাই'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('সনদ যাচাই'), 'title' => __('সনদ আসল কি না যাচাই করুন'), 'lead' => __('সনদে থাকা নম্বর ও স্টুডেন্টের জন্ম তারিখ লিখে যাচাই করুন।')])

<section style="padding:56px 0 88px">
<div class="wrap">
    <form method="GET" action="{{ route('verify') }}" class="pcard" style="max-width:560px;margin:0 auto;display:flex;flex-direction:column;gap:18px">
        @include('admin.partials.field', ['name' => 'certificate_no', 'label' => __('সনদ নম্বর'), 'req' => true, 'value' => request('certificate_no'), 'ph' => __('যেমন: MCT-C-2026-0001'), 'id' => 'vc'])
        @include('admin.partials.field', ['name' => 'dob', 'label' => __('জন্ম তারিখ'), 'req' => true, 'type' => 'date', 'value' => request('dob'), 'id' => 'vd'])
        <button class="btn p" type="submit">{{ __('যাচাই করুন') }}</button>
    </form>

    @if ($result)
        <div role="status" style="max-width:560px;margin:24px auto 0;padding:20px;border-radius:14px;background:#E3F0DD;color:#0E3A05;line-height:1.7">
            <b style="display:flex;align-items:center;gap:8px;font-size:18px">@include('partials.icon', ['n' => 'check', 'w' => 2.6]){{ __('সনদটি সঠিক') }}</b>
            <p style="margin:6px 0 0">{{ __('নাম') }}: <b>{{ $result->name_bn }}</b> ({{ $result->name_en }})<br>{{ __('কোর্স') }}: <b>{{ $result->batch->department->tr('course_name') ?: $result->batch->department->tr('name') }}</b> · {{ __('ব্যাচ') }}: <b>{{ $result->batch->code }}</b><br>{{ __('সনদ নং') }}: {{ $result->certificate_no }} · {{ __('প্রদানের তারিখ') }}: {{ bn_date($result->certificate_issued_on) }}</p>
        </div>
    @elseif ($result === false)
        <div role="alert" style="max-width:560px;margin:24px auto 0;padding:20px;border-radius:14px;background:#FCEBEC;color:#8A0A20;line-height:1.7">
            <b style="font-size:18px">{{ __('কোনো সনদ পাওয়া যায়নি') }}</b>
            <p style="margin:6px 0 0">{{ __('সনদ নম্বর ও জন্ম তারিখ আবার মিলিয়ে দেখুন। সমস্যা থাকলে') }} <a href="{{ route('contact') }}">{{ __('অফিসে যোগাযোগ করুন') }}</a>{{ __('।') }}</p>
        </div>
    @endif
</div>
</section>
@endsection
