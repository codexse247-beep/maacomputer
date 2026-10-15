@extends('layouts.admin')

@section('title', 'সেটিংস')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>সেটিংস</strong>@endsection

@php
    $tf = fn ($key, $label, $opts = []) => array_merge(['name' => $key, 'label' => $label, 'value' => $s[$key] ?? '', 'id' => 'set_'.$key], $opts);
    $area = fn ($key, $label, $rows = 3) => '<div class="ff"><label for="set_'.$key.'">'.e($label).'</label><textarea id="set_'.$key.'" name="'.$key.'" class="ta" rows="'.$rows.'">'.e(old($key, $s[$key] ?? '')).'</textarea></div>';
@endphp

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" style="display:flex;flex-direction:column;gap:24px">
    @csrf @method('PUT')
    <div class="pagehead">
        <div>
            <h1 class="h1">সেটিংস</h1>
            <p class="lead">প্রতিষ্ঠান, ওয়েবসাইট, ফি ও ব্যবহারকারীর সেটিংস। [বন্ধনীর ভেতরের] লেখাগুলো আসল তথ্য দিয়ে বদলান।</p>
        </div>
        <button class="btn p">পরিবর্তন সংরক্ষণ</button>
    </div>

    <section class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">প্রতিষ্ঠানের তথ্য</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:16px">
            @include('admin.partials.field', $tf('name', 'প্রতিষ্ঠানের নাম', ['req' => true]))
            @include('admin.partials.field', $tf('name_en', 'ইংরেজি নাম'))
            @include('admin.partials.field', $tf('phone', 'ফোন'))
            @include('admin.partials.field', $tf('email', 'ইমেইল'))
            @include('admin.partials.field', $tf('area', 'এলাকা'))
            @include('admin.partials.field', $tf('address', 'পূর্ণ ঠিকানা'))
            @include('admin.partials.field', $tf('hours', 'অফিস সময়'))
            @include('admin.partials.field', $tf('domain', 'ওয়েবসাইট ঠিকানা'))
        </div>
        @include('admin.partials.field', $tf('map_url', 'গুগল ম্যাপ লিংক (ঐচ্ছিক)', ['hint' => 'Google Maps › Share › Embed a map › লিংকের src অংশটি কপি করে দিন', 'ph' => 'https://www.google.com/maps/embed?pb=...']))
    </section>

    <section class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">ডিফল্ট কোর্স ফি</h2>
        <p class="mute" style="margin:0">নতুন ভর্তিতে এই ফি স্বয়ংক্রিয়ভাবে বসবে। আগে ভর্তি হওয়া স্টুডেন্টদের ফি বদলাবে না।</p>
        <div class="scroll">
            <table class="tbl" style="min-width:560px">
                <thead><tr><th class="th">বিভাগ</th><th class="th">কোর্স ফি (৳)</th><th class="th">ভর্তি ফি (৳)</th></tr></thead>
                <tbody>
                @foreach ($departments as $d)
                    <tr>
                        <td class="td"><b>{{ $d->name }}</b></td>
                        <td class="td"><label class="sr" for="cf{{ $d->id }}">{{ $d->name }} কোর্স ফি</label><input class="in" id="cf{{ $d->id }}" name="fees[{{ $d->id }}][course_fee]" inputmode="numeric" value="{{ old("fees.$d->id.course_fee", $d->course_fee) }}" style="max-width:160px"></td>
                        <td class="td"><label class="sr" for="af{{ $d->id }}">{{ $d->name }} ভর্তি ফি</label><input class="in" id="af{{ $d->id }}" name="fees[{{ $d->id }}][admission_fee]" inputmode="numeric" value="{{ old("fees.$d->id.admission_fee", $d->admission_fee) }}" style="max-width:160px"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">ওয়েবসাইটের লেখা</h2>
        {!! $area('about_intro', 'আমাদের সম্পর্কে — ভূমিকা', 3) !!}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr));gap:16px">
            {!! $area('mission', 'আমাদের লক্ষ্য') !!}
            {!! $area('vision', 'আমাদের দৃষ্টিভঙ্গি') !!}
            {!! $area('why_us', 'কেন আমরা') !!}
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:16px">
            @include('admin.partials.field', $tf('certified_count', 'সনদপ্রাপ্ত (সংখ্যা)', ['hint' => 'সিস্টেমে সনদ দেওয়া শুরু হলে আসল সংখ্যা দেখাবে']))
            @include('admin.partials.field', $tf('privacy_date', 'নীতির কার্যকর তারিখ'))
        </div>
        {!! $area('privacy', 'গোপনীয়তা নীতি ও শর্তাবলি ("## " দিয়ে শুরু লাইন হবে শিরোনাম)', 12) !!}
    </section>

    <section class="card" style="padding:24px;display:flex;flex-direction:column;gap:10px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">স্টুডেন্ট আইডি</h2>
        <p class="mute" style="margin:0;line-height:1.7">আইডি তৈরি হয় <b>MCT-ব্যাচের সংখ্যা-সিরিয়াল</b> ফরম্যাটে (যেমন GD-2607 ব্যাচের ৪ নম্বর স্টুডেন্ট → MCT-2607-004)। প্রতি ব্যাচে সিরিয়াল ০০১ থেকে শুরু হয়। সনদ নম্বর: MCT-C-বছর-সিরিয়াল।</p>
    </section>

    <div><button class="btn p">পরিবর্তন সংরক্ষণ</button></div>
</form>

<section id="users" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
    <h2 style="margin:0;font-size:20px;color:#0E3A05">ব্যবহারকারী ও ভূমিকা</h2>
    <p class="mute" style="margin:0">অ্যাডমিন সব কিছু পারেন। স্টাফ ও অ্যাকাউন্টস সেটিংস ছাড়া বাকি সব পারেন।</p>
    <div class="scroll">
        <table class="tbl" style="min-width:640px">
            <thead><tr><th class="th">নাম</th><th class="th">ইমেইল</th><th class="th">ভূমিকা</th><th class="th"></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td class="td" style="height:64px"><b>{{ $u->name }}</b>@if ($u->is(auth()->user())) <span class="mute">(আপনি)</span>@endif</td>
                    <td class="td">{{ $u->email }}</td>
                    <td class="td">
                        <form method="POST" action="{{ route('admin.settings.users.update', $u) }}" class="row" style="gap:8px">
                            @csrf @method('PUT')
                            <label class="sr" for="role{{ $u->id }}">{{ $u->name }} এর ভূমিকা</label>
                            <select class="sel" id="role{{ $u->id }}" name="role">@foreach (\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected($u->role === $k)>{{ $l }}</option>@endforeach</select>
                            <button class="btn sm">সংরক্ষণ</button>
                            <button class="btn sm" name="reset_password" value="1" onclick="return confirm('{{ $u->email }} এর নতুন পাসওয়ার্ড তৈরি করবেন?')">পাসওয়ার্ড রিসেট</button>
                        </form>
                    </td>
                    <td class="td" style="text-align:right">@unless ($u->is(auth()->user()))<form method="POST" action="{{ route('admin.settings.users.destroy', $u) }}" onsubmit="return confirm('{{ $u->name }} কে মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>@endunless</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <details>
        <summary class="btn" style="width:max-content">নতুন ব্যবহারকারী</summary>
        <form method="POST" action="{{ route('admin.settings.users.store') }}" style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;margin-top:16px">
            @csrf
            <div style="flex:1 1 220px">@include('admin.partials.field', ['name' => 'name', 'label' => 'নাম', 'req' => true, 'value' => '', 'id' => 'u_name'])</div>
            <div style="flex:1 1 220px">@include('admin.partials.field', ['name' => 'email', 'label' => 'ইমেইল', 'req' => true, 'type' => 'email', 'value' => '', 'id' => 'u_email'])</div>
            <div style="flex:1 1 180px">@include('admin.partials.select', ['name' => 'role', 'label' => 'ভূমিকা', 'value' => 'staff', 'options' => \App\Models\User::ROLES, 'id' => 'u_role'])</div>
            <button class="btn p">তৈরি করুন</button>
        </form>
    </details>
</section>

<section id="faq" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
    <h2 style="margin:0;font-size:20px;color:#0E3A05">প্রশ্নোত্তর (ওয়েবসাইট)</h2>
    @foreach ($faqs as $f)
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;align-items:flex-start;padding:12px 0;border-top:1px solid #DFE5DB">
            <div style="flex:1 1 300px;min-width:0"><b>{{ $f->question }}</b><div class="mute" style="white-space:pre-line">{{ $f->answer }}</div></div>
            <form method="POST" action="{{ route('admin.settings.faqs.destroy', $f) }}" onsubmit="return confirm('মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>
        </div>
    @endforeach
    <form method="POST" action="{{ route('admin.settings.faqs.store') }}" style="display:flex;flex-direction:column;gap:12px;border-top:1px solid #DFE5DB;padding-top:16px">
        @csrf
        @include('admin.partials.field', ['name' => 'question', 'label' => 'নতুন প্রশ্ন', 'value' => '', 'id' => 'fq_q'])
        <div class="ff"><label for="fq_a">উত্তর</label><textarea id="fq_a" name="answer" class="ta">{{ old('answer') }}</textarea></div>
        <div><button class="btn">প্রশ্নোত্তর যোগ করুন</button></div>
    </form>
</section>

<section id="gallery" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
    <h2 style="margin:0;font-size:20px;color:#0E3A05">গ্যালারি (ওয়েবসাইট)</h2>
    <form method="POST" action="{{ route('admin.settings.gallery.store') }}" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end">
        @csrf
        <div style="flex:1 1 220px">@include('admin.partials.upload', ['name' => 'photos[]', 'label' => 'ছবি (একসাথে কয়েকটি বাছতে পারেন)', 'btn' => 'ছবি বাছুন', 'accept' => 'image/*', 'multiple' => true, 'id' => 'g_photos'])</div>
        <div style="flex:1 1 180px">@include('admin.partials.select', ['name' => 'category', 'label' => 'ধরন', 'value' => 'lab', 'options' => \App\Models\GalleryPhoto::CATEGORIES, 'id' => 'g_cat'])</div>
        <div style="flex:1 1 220px">@include('admin.partials.field', ['name' => 'caption', 'label' => 'ক্যাপশন (ঐচ্ছিক)', 'value' => '', 'id' => 'g_cap'])</div>
        <button class="btn p">আপলোড</button>
    </form>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
        @foreach ($photos as $p)
            <figure style="margin:0;display:flex;flex-direction:column;gap:4px">
                <img src="{{ $p->url() }}" alt="{{ $p->caption }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:10px">
                <figcaption class="mute" style="font-size:13px">{{ \App\Models\GalleryPhoto::CATEGORIES[$p->category] ?? '' }}{{ $p->caption ? ' · '.$p->caption : '' }}</figcaption>
                <form method="POST" action="{{ route('admin.settings.gallery.destroy', $p) }}" onsubmit="return confirm('ছবিটি মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28;height:32px;padding:0">মুছুন</button></form>
            </figure>
        @endforeach
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('input[type=file]').forEach((input) => input.addEventListener('change', () => {
    const label = document.querySelector('label[for="' + input.id + '"]');
    if (label) label.textContent = input.files.length > 1 ? input.files.length + 'টি ছবি বাছাই হয়েছে' : (input.files[0] ? input.files[0].name : label.dataset.text);
}));
</script>
@endpush
