@extends('layouts.admin')

@php
    $batch = $student->batch;
    $att = $student->attendanceStats();
    $docTypes = $student->documents->groupBy('type');
    $field = fn ($label, $value) => ['l' => $label, 'v' => filled($value) ? $value : '—'];
    $age = $student->dob ? ' (বয়স '.bn($student->dob->age).' বছর)' : '';
@endphp

@section('title', $student->name_bn)
@section('crumbs')<a href="{{ route('admin.departments.index') }}">বিভাগ</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<a href="{{ route('admin.departments.show', $batch->department) }}">{{ $batch->department->name }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<a href="{{ route('admin.batches.show', $batch) }}">{{ $batch->code }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>{{ $student->name_bn }}</strong>@endsection

@section('content')
<div class="card" style="padding:24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:20px">
        @if ($student->photoUrl())
            <img src="{{ $student->photoUrl() }}" alt="{{ $student->name_bn }} এর ছবি" style="width:96px;height:96px;border-radius:20px;object-fit:cover;flex:none">
        @else
            <div style="width:96px;height:96px;border-radius:20px;background:#E3F0DD;border:2px dashed #9DBB93;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#176008;font-weight:600;font-size:13px;gap:2px;flex:none">@include('partials.icon', ['n' => 'user', 'size' => 26, 'w' => 1.8])ছবি নেই</div>
        @endif
        <div style="display:flex;flex-direction:column;gap:6px">
            <div class="row"><h1 class="h1" style="font-size:30px">{{ $student->name_bn }}</h1><span class="badge {{ $student->statusTone() }}">{{ $student->statusLabel() }}</span><span class="badge {{ $student->feeTone() }}">ফি {{ $student->feeLabel() }}</span></div>
            <div class="mute" style="font-size:15px">{{ $student->name_en }} · স্টুডেন্ট আইডি <b style="color:#14201A">{{ $student->student_code }}</b> · রোল {{ bn(str_pad($student->roll, 2, '0', STR_PAD_LEFT)) }}</div>
            <div class="mute" style="font-size:15px">{{ $batch->department->name }} · {{ $batch->label() }} · ধাপ ৪/৪</div>
        </div>
    </div>
    <div class="row">
        <a class="btn" href="{{ route('admin.students.id-card', $student) }}">আইডি কার্ড</a>
        <a class="btn" href="{{ route('admin.students.form', $student) }}" target="_blank">ভর্তি ফরম প্রিন্ট</a>
        <form method="POST" action="{{ route('admin.students.password', $student) }}" onsubmit="return confirm('নতুন পোর্টাল পাসওয়ার্ড তৈরি করবেন? আগের পাসওয়ার্ড আর কাজ করবে না।')">@csrf<button class="btn">পাসওয়ার্ড রিসেট</button></form>
        <a class="btn p" href="{{ route('admin.students.edit', $student) }}">@include('partials.icon', ['n' => 'edit', 'size' => 18])তথ্য সম্পাদনা / ব্যাচ পরিবর্তন</a>
    </div>
</div>

<nav aria-label="তথ্যের অংশ" style="display:flex;flex-wrap:wrap;gap:4px;padding:6px;background:#fff;border:1px solid #DFE5DB;border-radius:14px">
    <a class="tab on" href="#personal">ব্যক্তিগত তথ্য</a>
    <a class="tab" href="#contact">যোগাযোগ ও ঠিকানা</a>
    <a class="tab" href="#education">শিক্ষাগত যোগ্যতা</a>
    <a class="tab" href="#course">ভর্তি ও কোর্স</a>
    <a class="tab" href="#fees">ফি ও পেমেন্ট</a>
    <a class="tab" href="#results">উপস্থিতি ও ফলাফল</a>
    <a class="tab" href="#docs">ডকুমেন্ট ও নোট</a>
</nav>

<div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:24px">
<div style="flex:999 1 560px;min-width:0;display:flex;flex-direction:column;gap:24px">

    <section id="personal" class="card">
        <div class="sh"><h2>ব্যক্তিগত তথ্য</h2><a class="el" href="{{ route('admin.students.edit', $student) }}#s1">সম্পাদনা</a></div>
        <div class="fg">
            @foreach ([
                $field('নাম (বাংলা)', $student->name_bn), $field('Name (English)', $student->name_en),
                $field('পিতার নাম', $student->father_name), $field('মাতার নাম', $student->mother_name),
                $field('জন্ম তারিখ', $student->dob ? bn_date($student->dob).$age : null), $field('লিঙ্গ', $student->gender),
                $field('রক্তের গ্রুপ', $student->blood_group), $field('ধর্ম', $student->religion),
                $field('জাতীয়তা', $student->nationality), $field('জন্মনিবন্ধন / এনআইডি নম্বর', $student->maskedNid()),
                $field('বৈবাহিক অবস্থা', $student->marital),
            ] as $x)<div class="fld"><span>{{ $x['l'] }}</span><b>{{ $x['v'] }}</b></div>@endforeach
        </div>
        <div class="sh" style="border-top:1px solid #DFE5DB"><h2>অভিভাবকের তথ্য</h2></div>
        <div class="fg">
            @foreach ([
                $field('পিতার পেশা', $student->father_job), $field('মাতার পেশা', $student->mother_job),
                $field('অভিভাবকের মোবাইল', bn($student->guardian_phone)),
                $field('জরুরি যোগাযোগ', $student->emergency_name ? $student->emergency_name.($student->emergency_relation ? ' ('.$student->emergency_relation.')' : '').($student->emergency_phone ? ' · '.bn($student->emergency_phone) : '') : null),
            ] as $x)<div class="fld"><span>{{ $x['l'] }}</span><b>{{ $x['v'] }}</b></div>@endforeach
        </div>
    </section>

    <section id="contact" class="card">
        <div class="sh"><h2>যোগাযোগ</h2><a class="el" href="{{ route('admin.students.edit', $student) }}#s3">সম্পাদনা</a></div>
        <div class="fg">
            <div class="fld"><span>মোবাইল নম্বর</span><b><a href="tel:{{ $student->phone }}" style="color:inherit">{{ bn($student->phone) }}</a></b></div>
            <div class="fld"><span>হোয়াটসঅ্যাপ</span><b>{{ $student->whatsapp ? bn($student->whatsapp) : '—' }}</b></div>
            <div class="fld"><span>ইমেইল</span><b>{{ $student->email ?: '—' }}</b></div>
        </div>
        <div class="sh" style="border-top:1px solid #DFE5DB"><h2>ঠিকানা</h2></div>
        <div class="fg" style="grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr))">
            <div class="fld"><span>বর্তমান ঠিকানা</span><b>{{ $student->presentAddress() }}</b></div>
            <div class="fld"><span>স্থায়ী ঠিকানা</span><b>{{ $student->permanentAddress() }}</b></div>
        </div>
    </section>

    <section id="education" class="card" style="overflow:hidden">
        <div class="sh"><h2>শিক্ষাগত যোগ্যতা</h2><a class="el" href="{{ route('admin.students.edit', $student) }}#s4">সম্পাদনা</a></div>
        <div class="scroll">
            <table class="tbl" style="min-width:760px">
                <thead><tr><th class="th">পরীক্ষা</th><th class="th">প্রতিষ্ঠান</th><th class="th">বিভাগ / গ্রুপ</th><th class="th">বোর্ড / বিশ্ববিদ্যালয়</th><th class="th">পাসের সন</th><th class="th">ফলাফল</th></tr></thead>
                <tbody>
                @forelse ($student->education ?? [] as $e)
                    <tr><td class="td"><b>{{ $e['exam'] }}</b></td><td class="td">{{ $e['inst'] ?? '' }}</td><td class="td">{{ $e['group'] ?? '' }}</td><td class="td">{{ $e['board'] ?? '' }}</td><td class="td">{{ bn($e['year'] ?? '') }}</td><td class="td"><b>{{ $e['result'] ?? '' }}</b></td></tr>
                @empty
                    <tr><td class="td mute" colspan="6">শিক্ষাগত তথ্য দেওয়া হয়নি।</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="fg" style="padding-top:20px">
            <div class="fld"><span>পূর্বের কম্পিউটার জ্ঞান</span><b>{{ $student->prior_knowledge ?: '—' }}</b></div>
            <div class="fld"><span>কোর্সে ভর্তির উদ্দেশ্য</span><b>{{ $student->goal ?: '—' }}</b></div>
        </div>
    </section>

    <section id="course" class="card">
        <div class="sh"><h2>ভর্তি ও কোর্সের বিবরণ</h2><a class="el" href="{{ route('admin.students.edit', $student) }}#s5">সম্পাদনা</a></div>
        <div class="fg">
            @foreach ([
                $field('স্টুডেন্ট আইডি', $student->student_code), $field('রোল নম্বর', bn(str_pad($student->roll, 2, '0', STR_PAD_LEFT))),
                $field('ভর্তির তারিখ', bn_date($student->admission_date)), $field('ভর্তির ধরন', $student->admission_type),
                $field('বিভাগ', $batch->department->name), $field('ব্যাচ', $batch->label()),
                $field('কোর্স', $batch->department->course_name), $field('কোর্সের মেয়াদ', trim(($batch->department->duration ?? '').' ('.bn_date($batch->start_date).' – '.bn_date($batch->end_date).')')),
                $field('ক্লাস রুটিন', collect([$batch->days, $batch->class_time])->filter()->implode(', ')), $field('শিক্ষক', $batch->teacher),
                $field('রেফারেন্স', $student->reference), $field('ভর্তি গ্রহণকারী', $student->admittedBy?->name),
            ] as $x)<div class="fld"><span>{{ $x['l'] }}</span><b>{{ $x['v'] }}</b></div>@endforeach
        </div>
    </section>

    <section id="fees" class="card" style="overflow:hidden">
        <div class="sh"><h2>পেমেন্টের ইতিহাস</h2></div>
        <div class="scroll">
            <table class="tbl" style="min-width:720px">
                <thead><tr><th class="th">রসিদ নং</th><th class="th">তারিখ</th><th class="th">খাত</th><th class="th">পরিমাণ</th><th class="th">মাধ্যম</th><th class="th"></th></tr></thead>
                <tbody>
                @forelse ($student->payments as $p)
                    <tr>
                        <td class="td"><b>{{ $p->receipt_no }}</b></td><td class="td">{{ bn_date($p->paid_on) }}</td><td class="td">{{ $p->item }}</td><td class="td"><b>{{ taka($p->amount) }}</b></td><td class="td">{{ $p->method }}</td>
                        <td class="td" style="text-align:right;white-space:nowrap">
                            <a class="el" href="{{ route('admin.payments.receipt', $p) }}">রসিদ</a>
                            <form method="POST" action="{{ route('admin.payments.destroy', $p) }}" style="display:inline" onsubmit="return confirm('রসিদ {{ $p->receipt_no }} মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="td mute" colspan="6">এখনো কোনো পেমেন্ট নেই।</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <details class="panel" @if ($errors->hasAny(['amount', 'paid_on', 'item', 'method'])) open @endif>
            <summary>+ পেমেন্ট গ্রহণ</summary>
            <form method="POST" action="{{ route('admin.payments.store', $student) }}">
                @csrf
                <div class="fgrid">
                    @include('admin.partials.field', ['name' => 'amount', 'label' => 'পরিমাণ (৳)', 'req' => true, 'value' => $student->next_installment_amount ?: '', 'mode' => 'numeric'])
                    @include('admin.partials.field', ['name' => 'paid_on', 'label' => 'তারিখ', 'req' => true, 'type' => 'date', 'value' => now()])
                    @include('admin.partials.field', ['name' => 'item', 'label' => 'খাত', 'req' => true, 'value' => bn_ordinal($student->payments->count() + 1).' কিস্তি'])
                    @include('admin.partials.select', ['name' => 'method', 'label' => 'মাধ্যম', 'req' => true, 'value' => null, 'options' => $methods])
                    @include('admin.partials.field', ['name' => 'next_installment_date', 'label' => 'পরবর্তী কিস্তির তারিখ', 'type' => 'date', 'value' => '', 'id' => 'p_next_date'])
                    @include('admin.partials.field', ['name' => 'next_installment_amount', 'label' => 'পরবর্তী কিস্তির পরিমাণ (৳)', 'value' => '', 'mode' => 'numeric', 'id' => 'p_next_amt'])
                </div>
                <div style="padding:0 24px 24px"><button class="btn p">পেমেন্ট গ্রহণ ও রসিদ তৈরি</button></div>
            </form>
        </details>
    </section>

    <section id="results" class="card" style="overflow:hidden">
        <div class="sh"><h2>মূল্যায়ন ও ফলাফল</h2></div>
        <div class="scroll">
            <table class="tbl" style="min-width:620px">
                <thead><tr><th class="th">মূল্যায়ন</th><th class="th">তারিখ</th><th class="th">প্রাপ্ত নম্বর</th><th class="th">অবস্থা</th><th class="th"></th></tr></thead>
                <tbody>
                @forelse ($student->assessments as $m)
                    <tr>
                        <td class="td"><b>{{ $m->title }}</b>@if ($m->remark)<br><span class="mute" style="font-size:13px">{{ $m->remark }}</span>@endif</td><td class="td">{{ bn_date($m->held_on) }}</td><td class="td"><b>{{ $m->scoreText() }}</b> <span class="badge {{ $m->grade()[1] }}">{{ $m->grade()[0] }}</span></td><td class="td"><span class="badge {{ $m->statusTone() }}">{{ $m->statusLabel() }}</span>@unless ($m->published) <span class="badge b-n">খসড়া</span>@endunless</td>
                        <td class="td" style="text-align:right"><form method="POST" action="{{ route('admin.assessments.destroy', $m) }}" onsubmit="return confirm('এই ফলাফল মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form></td>
                    </tr>
                @empty
                    <tr><td class="td mute" colspan="5">এখনো কোনো ফলাফল নেই।</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <details class="panel" @if ($errors->hasAny(['title', 'score', 'max_score'])) open @endif>
            <summary>+ ফলাফল যোগ করুন</summary>
            <form method="POST" action="{{ route('admin.students.assessments', $student) }}">
                @csrf
                <div class="fgrid">
                    @include('admin.partials.field', ['name' => 'title', 'label' => 'মূল্যায়নের নাম', 'req' => true, 'value' => '', 'ph' => 'যেমন: মডিউল টেস্ট ১ — ফটোশপ বেসিক'])
                    @include('admin.partials.field', ['name' => 'held_on', 'label' => 'তারিখ', 'type' => 'date', 'value' => now()])
                    @include('admin.partials.field', ['name' => 'score', 'label' => 'প্রাপ্ত নম্বর', 'value' => '', 'mode' => 'numeric', 'hint' => 'পরীক্ষা না হলে খালি রাখুন'])
                    @include('admin.partials.field', ['name' => 'max_score', 'label' => 'পূর্ণ নম্বর', 'req' => true, 'value' => 100, 'mode' => 'numeric'])
                    @include('admin.partials.select', ['name' => 'status', 'label' => 'অবস্থা', 'req' => true, 'value' => 'passed', 'options' => collect(\App\Models\Assessment::STATUSES)->map(fn ($s) => $s[0])->all(), 'id' => 'a_status'])
                </div>
                <div style="padding:0 24px 24px"><button class="btn p">ফলাফল যোগ করুন</button></div>
            </form>
        </details>
    </section>

    <section id="docs" class="card">
        <div class="sh"><h2>ডকুমেন্ট</h2></div>
        <div style="padding:0 24px 16px;display:flex;flex-direction:column;gap:8px">
            @forelse ($student->documents as $doc)
                <a class="dl" href="{{ route('admin.documents.show', $doc) }}" target="_blank"><span>{{ \App\Models\StudentDocument::TYPES[$doc->type] ?? $doc->type }} <span class="mute" style="font-weight:500;font-size:14px">· {{ $doc->original_name }}</span></span>@include('partials.icon', ['n' => 'download', 'size' => 18])</a>
            @empty
                <p class="mute" style="margin:0">কোনো ডকুমেন্ট আপলোড হয়নি।</p>
            @endforelse
        </div>
        <details class="panel">
            <summary>+ ডকুমেন্ট আপলোড</summary>
            <form method="POST" action="{{ route('admin.students.documents', $student) }}" enctype="multipart/form-data">
                @csrf
                <div class="fgrid">
                    @include('admin.partials.upload', ['name' => 'photo', 'label' => 'পাসপোর্ট সাইজ ছবি', 'btn' => 'ছবি বাছুন', 'accept' => 'image/*'])
                    @include('admin.partials.upload', ['name' => 'doc_nid', 'label' => 'জন্মনিবন্ধন / এনআইডি কপি', 'btn' => 'ফাইল বাছুন', 'accept' => '.jpg,.jpeg,.png,.pdf'])
                    @include('admin.partials.upload', ['name' => 'doc_certificate[]', 'label' => 'শিক্ষাগত সনদের কপি', 'btn' => 'ফাইল বাছুন', 'accept' => '.jpg,.jpeg,.png,.pdf', 'multiple' => true])
                    @include('admin.partials.upload', ['name' => 'doc_form', 'label' => 'স্বাক্ষরিত ভর্তি ফরম', 'btn' => 'ফাইল বাছুন', 'accept' => '.jpg,.jpeg,.png,.pdf'])
                </div>
                <div style="padding:0 24px 24px"><button class="btn p">আপলোড করুন</button></div>
            </form>
        </details>

        <div class="sh" style="border-top:1px solid #DFE5DB"><h2>অভ্যন্তরীণ নোট</h2><span style="font-size:14px;color:#55635A">শুধু অ্যাডমিন দেখতে পারবেন</span></div>
        <div style="padding:0 24px 24px;display:flex;flex-direction:column;gap:12px">
            @foreach ($student->notes as $note)
                <div style="padding:12px 14px;border-radius:12px;background:#F5F7F3;line-height:1.6">
                    <div style="white-space:pre-line">{{ $note->body }}</div>
                    <div class="mute" style="font-size:13px;margin-top:4px">{{ $note->author?->name ?? 'অ্যাডমিন' }} · {{ bn_date($note->created_at) }}</div>
                </div>
            @endforeach
            <form method="POST" action="{{ route('admin.students.notes', $student) }}" style="display:flex;flex-direction:column;gap:12px">
                @csrf
                <label for="note" style="font-size:14px;font-weight:600;color:#55635A">নতুন নোট</label>
                <textarea id="note" name="body" class="ta" placeholder="এই স্টুডেন্ট সম্পর্কে কোনো বিশেষ তথ্য লিখুন…" required></textarea>
                <div><button class="btn">নোট সংরক্ষণ</button></div>
            </form>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('{{ $student->name_bn }} এর সব তথ্য, পেমেন্ট ও ডকুমেন্ট স্থায়ীভাবে মুছে যাবে। নিশ্চিত?')">
        @csrf @method('DELETE')
        <button class="btn d">স্টুডেন্ট মুছে ফেলুন</button>
        <span class="mute" style="font-size:14px;margin-left:8px">ভুল করে ভর্তি হলে ব্যবহার করুন। কোর্স ছেড়ে দিলে অবস্থা "বাদ পড়েছে" করুন।</span>
    </form>
</div>

<aside style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:24px">
    <div class="card" style="padding:24px;display:flex;flex-direction:column;gap:12px">
        <h2 style="margin:0;font-size:19px;font-weight:700;color:#0E3A05">সনদ</h2>
        @if ($student->certificate_no)
            <div class="fld"><span>সনদ নম্বর</span><b>{{ $student->certificate_no }}</b></div>
            <div class="fld"><span>প্রদানের তারিখ</span><b>{{ bn_date($student->certificate_issued_on) }}</b></div>
            <a class="el" style="padding:0" href="{{ route('verify', ['certificate_no' => $student->certificate_no, 'dob' => $student->dob?->format('Y-m-d')]) }}" target="_blank">ওয়েবসাইটে যাচাই করে দেখুন</a>
        @else
            <p class="mute" style="margin:0;font-size:15px">কোর্স শেষ হলে সনদ দিন। সনদ নম্বর তৈরি হবে এবং ওয়েবসাইটে যাচাই করা যাবে।</p>
            <form method="POST" action="{{ route('admin.students.certificate', $student) }}" onsubmit="return confirm('{{ $student->name_bn }} কে সনদ দেবেন? স্টুডেন্টের অবস্থা হবে কোর্স সম্পন্ন।')">@csrf<button class="btn">সনদ প্রদান করুন</button></form>
        @endif
    </div>

    <div class="card" style="padding:24px;display:flex;flex-direction:column;gap:16px">
        <h2 style="margin:0;font-size:19px;font-weight:700;color:#0E3A05">ফি-এর সারসংক্ষেপ</h2>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:16px">
            <div style="display:flex;justify-content:space-between"><span class="mute">কোর্স ফি</span><b>{{ taka($student->course_fee) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span class="mute">ভর্তি ফি</span><b>{{ taka($student->admission_fee) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span class="mute">ছাড়</span><b>{{ $student->discount ? '−'.taka($student->discount) : taka(0) }}</b></div>
            <div style="display:flex;justify-content:space-between;padding-top:10px;border-top:1px solid #DFE5DB"><span class="mute">মোট প্রদেয়</span><b>{{ taka($student->totalPayable()) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span class="mute">পরিশোধিত</span><b style="color:#176008">{{ taka($student->totalPaid()) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span class="mute">বকেয়া</span><b style="color:#B00E28;font-size:20px">{{ taka($student->totalDue()) }}</b></div>
        </div>
        <div class="bar" style="background:#FCEBEC"><span style="width:{{ min(100, $student->paidPercent()) }}%"></span></div>
        @if ($student->totalDue() && $student->next_installment_date)
            <div style="background:{{ $student->next_installment_date->isPast() ? '#FCEBEC;color:#8A0A20' : '#FFF3DB;color:#8A5300' }};border-radius:12px;padding:12px 14px;font-size:14px;line-height:1.6">পরবর্তী কিস্তি: @if ($student->next_installment_amount)<b>{{ taka($student->next_installment_amount) }}</b> · @endif{{ bn_date($student->next_installment_date) }}{{ $student->next_installment_date->isPast() ? ' (তারিখ পেরিয়ে গেছে)' : '' }}</div>
        @endif
    </div>

    <div class="card" style="padding:24px;display:flex;flex-direction:column;gap:18px">
        <h2 style="margin:0;font-size:19px;font-weight:700;color:#0E3A05">উপস্থিতি</h2>
        @if ($att['total'])
            <div style="display:flex;align-items:center;gap:20px">
                <div style="width:96px;height:96px;border-radius:50%;background:conic-gradient(#176008 0 {{ $att['percent'] }}%,#E3F0DD 0);display:flex;align-items:center;justify-content:center;flex:none"><div style="width:72px;height:72px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;color:#0E3A05">{{ bn($att['percent']) }}%</div></div>
                <div style="display:flex;flex-direction:column;font-size:15px;line-height:1.7"><span>মোট ক্লাস: <b>{{ bn($att['total']) }}টি</b></span><span>উপস্থিত: <b style="color:#176008">{{ bn($att['present']) }}টি</b></span><span>অনুপস্থিত: <b style="color:#B00E28">{{ bn($att['absent']) }}টি</b></span></div>
            </div>
        @else
            <p class="mute" style="margin:0">এখনো উপস্থিতি নেওয়া হয়নি। <a href="{{ route('admin.attendance.create', $batch) }}">উপস্থিতি নিন</a></p>
        @endif
    </div>

    <div class="card" style="padding:24px;display:flex;flex-direction:column;gap:14px">
        <h2 style="margin:0;font-size:19px;font-weight:700;color:#0E3A05">ডকুমেন্ট চেকলিস্ট</h2>
        @foreach (\App\Models\StudentDocument::TYPES as $type => $label)
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:15px"><span>{{ $label }}</span>
                @if ($docTypes->has($type))<span class="badge b-g">জমা হয়েছে</span>@else<span class="badge b-a">জমা বাকি</span>@endif</div>
        @endforeach
    </div>
</aside>
</div>
@endsection
