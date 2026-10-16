@extends('layouts.site')

@section('title', __('ভর্তি'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('ভর্তি'), 'title' => __('ভর্তির আবেদন করুন, বাকিটা আমরা দেখছি'), 'lead' => __('আবেদন পাঠালে আমাদের স্টাফ আপনার সাথে যোগাযোগ করে ব্যাচ ও ফি নিশ্চিত করবেন। চাইলে সরাসরি কেন্দ্রে এসেও ভর্তি হতে পারেন।')])

<section style="padding:72px 0 88px">
<div class="wrap" style="display:flex;flex-wrap:wrap;gap:40px;align-items:flex-start">
    <div style="flex:1 1 360px;min-width:0;display:flex;flex-direction:column;gap:36px">
        <div>
            <div class="eyebrow">@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => '#C8102E', 'color' => 'none']){{ __('ধাপগুলো') }}</div>
            <h2 style="margin:10px 0 0;font-size:clamp(26px,3vw,34px);line-height:1.35;font-weight:700;color:#0E3A05">{{ __('ভর্তি হবেন যেভাবে') }}</h2>
            <ol style="margin:22px 0 0;padding:0;list-style:none;display:flex;flex-direction:column;gap:18px">
                @foreach ([['বিভাগ বেছে নিন', 'লক্ষ্য অনুযায়ী কোর্স ঠিক করুন।'], ['আবেদন পাঠান বা কেন্দ্রে আসুন', 'ফরমটি পূরণ করুন, অথবা সরাসরি এসে ভর্তি ফরম জমা দিন।'], ['ব্যাচ ও ফি নিশ্চিত করুন', 'স্টাফ আপনাকে উপযুক্ত ব্যাচে যুক্ত করবেন এবং প্রথম পেমেন্ট নেবেন।'], ['স্টুডেন্ট আইডি পান', 'আইডি ও পোর্টাল লগইন নিয়ে ক্লাস শুরু করুন।']] as $i => [$t, $d])
                <li style="display:flex;gap:16px;align-items:flex-start"><span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:#176008;color:#fff;font-weight:700;flex:none">{{ bn($i + 1) }}</span><div><b style="font-size:18px;color:#0E3A05">{{ __($t) }}</b><div style="color:#55635A;font-size:16px">{{ __($d) }}</div></div></li>
                @endforeach
            </ol>
        </div>
        <div style="padding:24px;border:1px solid #DFE5DB;border-radius:18px;background:#F8FBF6">
            <h3 style="margin:0 0 14px;font-size:20px;font-weight:700;color:#0E3A05">{{ __('সাথে আনুন') }}</h3>
            <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:10px;font-size:16px">
                @foreach (['পাসপোর্ট সাইজ ছবি', 'জন্মনিবন্ধন বা এনআইডি-র কপি', 'সর্বশেষ শিক্ষাগত সনদের কপি', 'অভিভাবকের মোবাইল নম্বর'] as $item)
                    <li style="display:flex;gap:10px;align-items:flex-start">@include('partials.icon', ['n' => 'check', 'color' => '#176008', 'w' => 2.4, 'style' => 'flex:none;margin-top:3px']){{ __($item) }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <form method="POST" action="{{ route('admission.store') }}" style="flex:1.3 1 460px;min-width:0;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:20px;background:#fff;box-shadow:0 12px 40px rgba(14,58,5,.07);display:flex;flex-direction:column;gap:20px">
        @csrf
        <div>
            <h2 style="margin:0;font-size:26px;line-height:1.35;font-weight:700;color:#0E3A05">{{ __('অনলাইন ভর্তির আবেদন') }}</h2>
            <p style="margin:6px 0 0;color:#55635A;font-size:16px">{{ __('সম্পূর্ণ ভর্তি ফরম কেন্দ্রে জমা দিতে হবে। এখানে শুধু প্রাথমিক তথ্য দিন।') }}</p>
        </div>
        @include('partials.site-flash')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:18px 20px">
            @include('admin.partials.field', ['name' => 'name', 'label' => __('নাম'), 'req' => true, 'value' => '', 'ph' => __('পূর্ণ নাম'), 'id' => 'a-name'])
            @include('admin.partials.field', ['name' => 'father_name', 'label' => __('পিতার নাম'), 'req' => true, 'value' => '', 'ph' => __('পিতার নাম'), 'id' => 'a-father'])
            @include('admin.partials.field', ['name' => 'phone', 'label' => __('মোবাইল নম্বর'), 'req' => true, 'value' => '', 'type' => 'tel', 'ph' => '01XXX-XXXXXX', 'id' => 'a-phone'])
            @include('admin.partials.field', ['name' => 'email', 'label' => __('ইমেইল'), 'value' => '', 'type' => 'email', 'ph' => 'name@example.com', 'id' => 'a-email'])
            @include('admin.partials.field', ['name' => 'dob', 'label' => __('জন্ম তারিখ'), 'value' => '', 'type' => 'date', 'id' => 'a-dob'])
            @include('admin.partials.select', ['name' => 'education', 'label' => __('সর্বশেষ শিক্ষাগত যোগ্যতা'), 'value' => null, 'options' => $education, 'blank' => true, 'id' => 'a-edu'])
            @include('admin.partials.select', ['name' => 'department_id', 'label' => __('বিভাগ'), 'req' => true, 'value' => $selected, 'options' => $departments->mapWithKeys(fn ($d) => [$d->id => $d->tr('name')])->all(), 'blank' => true, 'id' => 'a-dept'])
            @include('admin.partials.select', ['name' => 'preferred_time', 'label' => __('পছন্দের ক্লাসের সময়'), 'value' => null, 'options' => $times, 'id' => 'a-time'])
        </div>
        <div class="ff"><label for="a-msg">{{ __('অতিরিক্ত কিছু জানাতে চাইলে') }}</label><textarea id="a-msg" name="message" class="ta" placeholder="{{ __('যেমন: আগে কম্পিউটার চালানোর অভিজ্ঞতা আছে কিনা') }}">{{ old('message') }}</textarea></div>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:14px 20px">
            <button class="btn p" type="submit">{{ __('আবেদন পাঠান') }}</button>
            <span style="color:#55635A;font-size:15px">{{ __('পাঠানোর পর আমরা আপনাকে ফোন করব।') }}</span>
        </div>
    </form>
</div>
</section>
@endsection
