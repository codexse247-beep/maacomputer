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

    <section id="english" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">ইংরেজি ওয়েবসাইটের লেখা</h2>
        <p class="mute" style="margin:0">ওয়েবসাইটের ওপরে "English" চাপলে এই লেখাগুলো দেখাবে। খালি রাখলে বাংলা লেখাই দেখাবে। বিভাগ, শিক্ষক ও প্রশ্নোত্তরের ইংরেজি তাদের নিজের ফরমে দিন।</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:16px">
            @include('admin.partials.field', $tf('area_en', 'Area'))
            @include('admin.partials.field', $tf('address_en', 'Full address'))
            @include('admin.partials.field', $tf('hours_en', 'Office hours'))
            @include('admin.partials.field', $tf('privacy_date_en', 'Policy effective date'))
        </div>
        {!! $area('about_intro_en', 'About us — introduction', 3) !!}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr));gap:16px">
            {!! $area('mission_en', 'Our mission') !!}
            {!! $area('vision_en', 'Our vision') !!}
            {!! $area('why_us_en', 'Why choose us') !!}
        </div>
        {!! $area('privacy_en', 'Privacy policy and terms ("## " starts a heading)', 8) !!}
    </section>

    <section id="google" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">Google লগইন <span class="badge {{ \App\Http\Controllers\GoogleLoginController::enabled() ? 'b-g' : 'b-n' }}" style="vertical-align:middle">{{ \App\Http\Controllers\GoogleLoginController::enabled() ? 'চালু' : 'বন্ধ' }}</span></h2>
        <p class="mute" style="margin:0;line-height:1.7">চালু হলে স্টুডেন্ট ও অ্যাডমিন লগইন পাতায় "Google দিয়ে সাইন-ইন করুন" বোতাম আসবে। শুধু তারাই ঢুকতে পারবে যাদের Gmail কোনো স্টাফ অ্যাকাউন্টের ইমেইল বা স্টুডেন্টের প্রোফাইলের ইমেইলের সাথে মেলে।</p>
        <div class="ff">
            <label for="g_redirect">Authorized redirect URI (Google Cloud-এ এটি কপি করে দিন)</label>
            <input class="in" id="g_redirect" readonly value="{{ route('google.callback') }}" onclick="this.select()" style="font-family:monospace">
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr));gap:16px">
            @include('admin.partials.field', $tf('google_client_id', 'Client ID', ['ph' => '1234…apps.googleusercontent.com']))
            <div class="ff">
                <label for="set_google_client_secret">Client secret</label>
                <input class="in" id="set_google_client_secret" type="password" name="google_client_secret" autocomplete="new-password" placeholder="{{ filled($s['google_client_secret']) ? '•••••••• (সংরক্ষিত — বদলাতে নতুনটি লিখুন)' : 'GOCSPX-…' }}">
                @error('google_client_secret')<span class="errm">{{ $message }}</span>@enderror
            </div>
        </div>
        @if (\App\Http\Controllers\GoogleLoginController::enabled())
            <label class="sw"><input type="checkbox" name="google_remove" value="1">Google লগইন বন্ধ করুন (Client ID ও secret মুছে যাবে)</label>
        @endif
    </section>

    <section id="pay" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">অনলাইন পেমেন্ট (বিকাশ / নগদ / রকেট)</h2>
        <p class="mute" style="margin:0">যে নম্বর দেবেন, স্টুডেন্ট পোর্টালে সেই মাধ্যমে ফি দেওয়ার অপশন দেখাবে। স্টুডেন্ট ট্রানজেকশন আইডি জমা দেবে, আপনি "অনলাইন পেমেন্ট" পাতায় মিলিয়ে গ্রহণ করবেন। খালি রাখলে বন্ধ থাকবে।</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr));gap:16px">
            @include('admin.partials.field', $tf('bkash_number', 'বিকাশ নম্বর', ['ph' => '01XXXXXXXXX', 'mode' => 'numeric']))
            @include('admin.partials.field', $tf('nagad_number', 'নগদ নম্বর', ['ph' => '01XXXXXXXXX', 'mode' => 'numeric']))
            @include('admin.partials.field', $tf('rocket_number', 'রকেট নম্বর', ['ph' => '01XXXXXXXXXX', 'mode' => 'numeric']))
        </div>
        {!! $area('pay_note', 'স্টুডেন্টদের জন্য নির্দেশনা', 3) !!}
    </section>

    <section id="email" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">ইমেইল</h2>
        <p class="mute" style="margin:0;line-height:1.7">ভর্তির আইডি-পাসওয়ার্ড, পেমেন্টের রসিদ, কিস্তির রিমাইন্ডার ও নোটিশ স্টুডেন্টের ইমেইলে যাবে (যাদের ইমেইল ঠিকানা আছে)।
            জিমেইল ব্যবহার করলে: Google অ্যাকাউন্টে 2-Step Verification চালু করে <b>App password</b> তৈরি করুন, সেটি নিচের পাসওয়ার্ড ঘরে দিন। সংরক্ষণের পর "ইমেইল" পাতা থেকে পরীক্ষামূলক ইমেইল পাঠিয়ে দেখুন।</p>
        <label class="sw"><input type="hidden" name="mail_enabled" value="0"><input type="checkbox" name="mail_enabled" value="1" @checked(old('mail_enabled', $s['mail_enabled']) === '1')>ইমেইল পাঠানো চালু</label>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(220px,100%),1fr));gap:16px">
            @include('admin.partials.field', $tf('mail_username', 'ইমেইল ঠিকানা (লগইন)', ['type' => 'email', 'ph' => 'maacomputer@gmail.com']))
            <div class="ff">
                <label for="set_mail_password">পাসওয়ার্ড / App password</label>
                <input class="in" id="set_mail_password" type="password" name="mail_password" autocomplete="new-password" placeholder="{{ filled($s['mail_password']) ? '•••••••• (সংরক্ষিত — বদলাতে নতুনটি লিখুন)' : '' }}">
                @error('mail_password')<span class="errm">{{ $message }}</span>@enderror
            </div>
            @include('admin.partials.field', $tf('mail_host', 'SMTP সার্ভার', ['hint' => 'জিমেইল: smtp.gmail.com']))
            @include('admin.partials.field', $tf('mail_port', 'পোর্ট', ['mode' => 'numeric', 'hint' => 'জিমেইল: 587']))
            @include('admin.partials.field', $tf('mail_from', 'উত্তর যাবে যে ঠিকানায় (Reply-To, ঐচ্ছিক)', ['type' => 'email', 'hint' => 'Gmail-এর নিয়মে প্রেরক সবসময় ওপরের Gmail-ই থাকবে; স্টুডেন্ট "Reply" করলে উত্তর এই ঠিকানায় যাবে']))
        </div>

        <div style="border-top:1px solid #DFE5DB;padding-top:16px;display:flex;flex-direction:column;gap:12px">
            <b style="color:#0E3A05">কিস্তির স্বয়ংক্রিয় রিমাইন্ডার</b>
            <label class="sw"><input type="hidden" name="due_auto" value="0"><input type="checkbox" name="due_auto" value="1" @checked(old('due_auto', $s['due_auto']) === '1')>প্রতিদিন সকালে নিজে থেকে রিমাইন্ডার ইমেইল পাঠাবে</label>
            <div style="max-width:260px">@include('admin.partials.field', $tf('due_days_before', 'কিস্তির কত দিন আগে', ['mode' => 'numeric', 'hint' => 'এই দিনে এবং কিস্তির দিনে পাঠাবে']))</div>
        </div>

        <details style="border-top:1px solid #DFE5DB;padding-top:16px">
            <summary style="cursor:pointer;font-weight:700;color:#0E3A05">ইমেইলের লেখা</summary>
            <p class="mute" style="margin:12px 0;font-size:14px">এই শব্দগুলো নিজে থেকে বসবে: {{ \App\Support\Notifier::PLACEHOLDERS }}</p>
            <div style="display:flex;flex-direction:column;gap:18px">
                @foreach (\App\Support\Notifier::EVENTS as $event => [$label])
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <label class="sw"><input type="hidden" name="notify_{{ $event }}" value="0"><input type="checkbox" name="notify_{{ $event }}" value="1" @checked(old('notify_'.$event, $s['notify_'.$event]) === '1')><b>{{ $label }}</b></label>
                        <label class="sr" for="set_tpl_{{ $event }}">{{ $label }} — লেখা</label>
                        <textarea id="set_tpl_{{ $event }}" name="tpl_{{ $event }}" class="ta" rows="6">{{ old('tpl_'.$event, $s['tpl_'.$event]) }}</textarea>
                    </div>
                @endforeach
            </div>
        </details>
    </section>

    <div><button class="btn p">পরিবর্তন সংরক্ষণ</button></div>
</form>

<section id="users" class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
    <h2 style="margin:0;font-size:20px;color:#0E3A05">ব্যবহারকারী ও ভূমিকা</h2>
    <ul class="mute" style="margin:0;padding-left:20px;line-height:1.8">
        @foreach (\App\Models\User::ROLES as $k => $l)<li><b style="color:#14201A">{{ $l }}:</b> {{ \App\Models\User::ROLE_HELP[$k] }}</li>@endforeach
    </ul>
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
        @include('admin.partials.english', ['model' => new \App\Models\Faq, 'fields' => ['question' => ['Question', 'text'], 'answer' => ['Answer', 'textarea']], 'style' => 'border:1px solid #DFE5DB;border-radius:12px'])
        <div><button class="btn">প্রশ্নোত্তর যোগ করুন</button></div>
    </form>
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
