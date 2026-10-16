@extends('layouts.site')

@section('title', __('যোগাযোগ'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('যোগাযোগ'), 'title' => __('আমাদের সাথে কথা বলুন'), 'lead' => __('ভর্তি, কোর্স বা ফি নিয়ে যেকোনো প্রশ্ন থাকলে ফোন করুন, মেসেজ পাঠান বা সরাসরি কেন্দ্রে আসুন।')])

<section style="padding:56px 0 88px">
<div class="wrap" style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
    <div style="flex:1 1 340px;min-width:0;display:flex;flex-direction:column;gap:16px">
        @foreach ([['pin', __('ঠিকানা'), setting_t('area').' — '.setting_t('address')], ['phone', __('ফোন'), setting('phone')], ['mail', __('ইমেইল'), setting('email')], ['clock', __('অফিস সময়'), setting_t('hours')]] as [$icon, $label, $value])
        <div style="display:flex;gap:16px;padding:20px;border:1px solid #DFE5DB;border-radius:16px;background:#fff">
            <span style="flex:none;width:48px;height:48px;border-radius:12px;background:#E3F0DD;color:#176008;display:flex;align-items:center;justify-content:center" aria-hidden="true">@include('partials.icon', ['n' => $icon, 'size' => 22])</span>
            <span style="display:flex;flex-direction:column;line-height:1.5;min-width:0"><b style="font-size:17px;color:#0E3A05">{{ $label }}</b><span style="font-size:16px;color:#3F4D44;overflow-wrap:anywhere">{{ $value }}</span></span>
        </div>
        @endforeach
        @if (setting('map_url'))
            <iframe src="{{ setting('map_url') }}" title="{{ __('কেন্দ্রের ম্যাপ') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="width:100%;height:260px;border:0;border-radius:16px"></iframe>
        @else
            <div class="phbox" style="height:220px;border-radius:16px">{{ __('ম্যাপ শীঘ্রই যোগ হবে') }}</div>
        @endif
    </div>

    <form method="POST" action="{{ route('contact.store') }}" style="flex:1 1 440px;min-width:0;display:flex;flex-direction:column;gap:18px;padding:clamp(20px,3vw,32px);border:1px solid #DFE5DB;border-radius:18px;background:#fff">
        @csrf
        <h2 style="margin:0;font-size:24px;color:#0E3A05">{{ __('মেসেজ পাঠান') }}</h2>
        @include('partials.site-flash')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(220px,100%),1fr));gap:18px">
            @include('admin.partials.field', ['name' => 'name', 'label' => __('আপনার নাম'), 'req' => true, 'value' => '', 'ph' => __('পূর্ণ নাম'), 'id' => 'cn'])
            @include('admin.partials.field', ['name' => 'phone', 'label' => __('মোবাইল নম্বর'), 'req' => true, 'value' => '', 'type' => 'tel', 'ph' => '01XXXXXXXXX', 'id' => 'cp'])
        </div>
        @include('admin.partials.field', ['name' => 'subject', 'label' => __('বিষয়'), 'value' => '', 'ph' => __('যেমন: গ্রাফিক ডিজাইনে ভর্তি'), 'id' => 'cs'])
        <div class="ff"><label for="cm">{{ __('আপনার বার্তা') }} <span class="req">*</span></label><textarea class="ta" id="cm" name="message" required placeholder="{{ __('এখানে লিখুন') }}" style="min-height:130px">{{ old('message') }}</textarea></div>
        <div><button class="btn p" type="submit">{{ __('মেসেজ পাঠান') }}</button></div>
    </form>
</div>
</section>
@endsection
