@extends('layouts.admin')

@php
    $editing = $student->exists;
    $edu = collect($student->education ?? [])->keyBy('exam');
    $batchData = $departments->mapWithKeys(fn ($d) => [$d->id => [
        'course' => $d->course_name ?: $d->name,
        'course_fee' => $d->course_fee,
        'admission_fee' => $d->admission_fee,
        'batches' => $d->batches->map(fn ($b) => [
            'id' => $b->id,
            'label' => $b->label().' — '.bn(max(0, $b->capacity - $b->active_students_count)).'টি আসন খালি',
            'routine' => collect([$b->days, $b->class_time])->filter()->implode(', ') ?: '—',
            'term' => bn_date($b->start_date).' – '.bn_date($b->end_date),
            'teacher' => $b->teacher ?: '—',
            'id_preview' => 'MCT-'.$b->serial().'-…',
        ])->values(),
    ]]);
    $selBatch = (int) old('batch_id', $student->batch_id);
    $selDept = $departments->first(fn ($d) => $d->batches->contains('id', $selBatch))?->id ?? $departments->first()?->id;
    $f = fn ($name, $label, $opts = []) => array_merge(['name' => $name, 'label' => $label, 'value' => $student->{$name}], $opts);
@endphp

@section('title', $editing ? 'তথ্য সম্পাদনা — '.$student->name_bn : 'নতুন ভর্তি')
@section('crumbs')
    @if ($editing)
        <a href="{{ route('admin.batches.show', $student->batch) }}">{{ $student->batch->code }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<a href="{{ route('admin.students.show', $student) }}">{{ $student->name_bn }}</a>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>সম্পাদনা</strong>
    @else
        <span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>নতুন ভর্তি</strong>
    @endif
@endsection

@section('content')
<div>
    <h1 class="h1">{{ $editing ? 'তথ্য সম্পাদনা — '.$student->student_code : 'নতুন ভর্তি ফরম' }}</h1>
    <p class="lead">বিভাগ ও ব্যাচ বেছে নিলে স্টুডেন্ট সরাসরি সেই ব্যাচের তালিকায় যুক্ত হবে এবং আইডি স্বয়ংক্রিয়ভাবে তৈরি হবে। <span class="req">*</span> চিহ্নিত ঘর আবশ্যক।</p>
</div>

@if ($departments->flatMap->batches->isEmpty())
    <div class="flash err">ভর্তির আগে অন্তত একটি ব্যাচ খুলতে হবে। <a href="{{ route('admin.departments.index') }}">বিভাগ ও ব্যাচ</a> থেকে ব্যাচ খুলুন।</div>
@endif

<div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:24px">
    <nav aria-label="ফরমের ধাপ" class="card" style="flex:1 1 240px;max-width:100%;padding:12px;display:flex;flex-direction:column;gap:2px;position:sticky;top:16px">
        <a class="step" href="#s1"><b>১</b>ব্যক্তিগত তথ্য</a>
        <a class="step" href="#s2"><b>২</b>অভিভাবকের তথ্য</a>
        <a class="step" href="#s3"><b>৩</b>যোগাযোগ ও ঠিকানা</a>
        <a class="step" href="#s4"><b>৪</b>শিক্ষাগত যোগ্যতা</a>
        <a class="step" href="#s5"><b>৫</b>বিভাগ, ব্যাচ ও কোর্স</a>
        <a class="step" href="#s6"><b>৬</b>ফি ও পেমেন্ট</a>
        <a class="step" href="#s7"><b>৭</b>ডকুমেন্ট আপলোড</a>
    </nav>

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.students.update', $student) : route('admin.students.store') }}" style="flex:999 1 560px;min-width:0;display:flex;flex-direction:column;gap:24px">
        @csrf
        @if (! empty($enquiry))
            <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
            <div class="flash ok" role="status">অনলাইন আবেদন থেকে ভর্তি: <b>{{ $enquiry->name }}</b> ({{ bn($enquiry->phone) }})। আবেদনের তথ্য বসানো হয়েছে — বাকি ঘরগুলো পূরণ করুন।</div>
        @endif
        @if ($editing) @method('PUT') @endif

        <section id="s1" class="card">
            <div class="sh left"><span class="no">১</span><h2>ব্যক্তিগত তথ্য</h2></div>
            <div class="fgrid">
                @include('admin.partials.field', $f('name_bn', 'নাম (বাংলা)', ['req' => true, 'ph' => 'পূর্ণ নাম বাংলায়']))
                @include('admin.partials.field', $f('name_en', 'নাম (ইংরেজি)', ['req' => true, 'ph' => 'Full name in English']))
                @include('admin.partials.field', $f('father_name', 'পিতার নাম', ['req' => true]))
                @include('admin.partials.field', $f('mother_name', 'মাতার নাম', ['req' => true]))
                @include('admin.partials.field', $f('dob', 'জন্ম তারিখ', ['type' => 'date']))
                @include('admin.partials.field', $f('nationality', 'জাতীয়তা', ['ph' => 'বাংলাদেশি']))
                @include('admin.partials.field', $f('nid', 'জন্মনিবন্ধন / এনআইডি নম্বর', ['ph' => 'নম্বর লিখুন', 'mode' => 'numeric']))
                @include('admin.partials.select', ['name' => 'gender', 'label' => 'লিঙ্গ', 'req' => true, 'value' => $student->gender, 'options' => ['পুরুষ', 'মহিলা', 'অন্যান্য'], 'blank' => true])
                @include('admin.partials.select', ['name' => 'blood_group', 'label' => 'রক্তের গ্রুপ', 'value' => $student->blood_group, 'options' => ['A+', 'A−', 'B+', 'B−', 'O+', 'O−', 'AB+', 'AB−'], 'blank' => true])
                @include('admin.partials.select', ['name' => 'religion', 'label' => 'ধর্ম', 'value' => $student->religion, 'options' => ['ইসলাম', 'হিন্দু', 'বৌদ্ধ', 'খ্রিস্টান', 'অন্যান্য'], 'blank' => true])
                @include('admin.partials.select', ['name' => 'marital', 'label' => 'বৈবাহিক অবস্থা', 'value' => $student->marital, 'options' => ['অবিবাহিত', 'বিবাহিত']])
            </div>
        </section>

        <section id="s2" class="card">
            <div class="sh left"><span class="no">২</span><h2>অভিভাবকের তথ্য</h2></div>
            <div class="fgrid">
                @include('admin.partials.field', $f('father_job', 'পিতার পেশা'))
                @include('admin.partials.field', $f('mother_job', 'মাতার পেশা'))
                @include('admin.partials.field', $f('guardian_phone', 'অভিভাবকের মোবাইল', ['req' => true, 'type' => 'tel', 'ph' => '01XXX-XXXXXX']))
                @include('admin.partials.field', $f('emergency_name', 'জরুরি যোগাযোগ — নাম'))
                @include('admin.partials.field', $f('emergency_relation', 'জরুরি যোগাযোগ — সম্পর্ক', ['ph' => 'যেমন: ভাই, চাচা']))
                @include('admin.partials.field', $f('emergency_phone', 'জরুরি যোগাযোগ — মোবাইল', ['type' => 'tel', 'ph' => '01XXX-XXXXXX']))
            </div>
        </section>

        <section id="s3" class="card">
            <div class="sh left"><span class="no">৩</span><h2>যোগাযোগ ও ঠিকানা</h2></div>
            <div class="fgrid">
                @include('admin.partials.field', $f('phone', 'স্টুডেন্টের মোবাইল', ['req' => true, 'type' => 'tel', 'ph' => '01XXX-XXXXXX']))
                @include('admin.partials.field', $f('whatsapp', 'হোয়াটসঅ্যাপ নম্বর', ['type' => 'tel', 'ph' => '01XXX-XXXXXX']))
                @include('admin.partials.field', $f('email', 'ইমেইল', ['type' => 'email', 'ph' => 'name@example.com']))
            </div>
            <div style="padding:0 24px 8px;font-weight:700;color:#0E3A05;font-size:16px">বর্তমান ঠিকানা</div>
            <div class="fgrid">
                @include('admin.partials.field', $f('present_village', 'গ্রাম / রোড / বাসা', ['req' => true]))
                @include('admin.partials.field', $f('present_po', 'ডাকঘর'))
                @include('admin.partials.field', $f('present_upazila', 'উপজেলা', ['req' => true, 'ph' => 'যেমন: মীরসরাই']))
                @include('admin.partials.field', $f('present_district', 'জেলা', ['req' => true, 'ph' => 'যেমন: চট্টগ্রাম']))
            </div>
            <div style="padding:0 24px 12px" class="row">
                <span style="font-weight:700;color:#0E3A05;font-size:16px">স্থায়ী ঠিকানা</span>
                <label for="same" style="display:inline-flex;align-items:center;gap:10px;min-height:44px;font-size:15px;cursor:pointer"><input id="same" name="same_address" value="1" type="checkbox" style="width:20px;height:20px;accent-color:#176008" @checked(old('same_address'))>বর্তমান ঠিকানার মতোই</label>
            </div>
            <div class="fgrid" id="perm">
                @include('admin.partials.field', $f('perm_village', 'গ্রাম / রোড / বাসা'))
                @include('admin.partials.field', $f('perm_po', 'ডাকঘর'))
                @include('admin.partials.field', $f('perm_upazila', 'উপজেলা'))
                @include('admin.partials.field', $f('perm_district', 'জেলা'))
            </div>
        </section>

        <section id="s4" class="card">
            <div class="sh left"><span class="no">৪</span><h2>শিক্ষাগত যোগ্যতা</h2></div>
            <div class="scroll" style="padding:0 24px 8px">
                <div style="min-width:880px;display:grid;grid-template-columns:130px 2fr 1fr 1.4fr 0.8fr 1fr;gap:10px 12px;align-items:center">
                    <div class="eh">পরীক্ষা</div><div class="eh">প্রতিষ্ঠান</div><div class="eh">গ্রুপ / বিভাগ</div><div class="eh">বোর্ড / বিশ্ববিদ্যালয়</div><div class="eh">পাসের সন</div><div class="eh">ফলাফল</div>
                    @foreach ($exams as $i => $exam)
                        @php $row = $edu->get($exam, []); @endphp
                        <div style="font-weight:600;font-size:15px">{{ $exam }}</div>
                        <input class="in" type="text" name="edu[{{ $i }}][inst]" value="{{ old("edu.$i.inst", $row['inst'] ?? '') }}" aria-label="{{ $exam }} — প্রতিষ্ঠান" placeholder="প্রতিষ্ঠানের নাম">
                        <input class="in" type="text" name="edu[{{ $i }}][group]" value="{{ old("edu.$i.group", $row['group'] ?? '') }}" aria-label="{{ $exam }} — গ্রুপ বা বিভাগ" placeholder="যেমন: বিজ্ঞান">
                        <input class="in" type="text" name="edu[{{ $i }}][board]" value="{{ old("edu.$i.board", $row['board'] ?? '') }}" aria-label="{{ $exam }} — বোর্ড বা বিশ্ববিদ্যালয়" placeholder="যেমন: চট্টগ্রাম বোর্ড">
                        <input class="in" type="text" name="edu[{{ $i }}][year]" value="{{ old("edu.$i.year", $row['year'] ?? '') }}" inputmode="numeric" aria-label="{{ $exam }} — পাসের সন" placeholder="সাল">
                        <input class="in" type="text" name="edu[{{ $i }}][result]" value="{{ old("edu.$i.result", $row['result'] ?? '') }}" aria-label="{{ $exam }} — ফলাফল" placeholder="জিপিএ / বিভাগ">
                    @endforeach
                </div>
            </div>
            <div class="fgrid" style="padding-top:16px">
                @include('admin.partials.select', ['name' => 'prior_knowledge', 'label' => 'পূর্বের কম্পিউটার জ্ঞান', 'value' => $student->prior_knowledge, 'options' => ['নেই', 'প্রাথমিক', 'মধ্যম', 'ভালো']])
                @include('admin.partials.field', $f('goal', 'কোর্সে ভর্তির উদ্দেশ্য', ['ph' => 'যেমন: চাকরি, ফ্রিল্যান্সিং, ব্যবসা']))
            </div>
        </section>

        <section id="s5" class="card">
            <div class="sh left"><span class="no">৫</span><h2>বিভাগ, ব্যাচ ও কোর্স</h2></div>
            <div class="fgrid">
                <div class="ff"><label for="dept">বিভাগ <span class="req">*</span></label>
                    <select id="dept" class="in">
                        @foreach ($departments as $d)<option value="{{ $d->id }}" @selected($d->id === $selDept)>{{ $d->name }}</option>@endforeach
                    </select>
                    <small>প্রথমে বিভাগ বাছুন</small></div>
                <div class="ff"><label for="batch">ব্যাচ <span class="req">*</span></label>
                    <select id="batch" name="batch_id" class="in {{ $errors->has('batch_id') ? 'bad' : '' }}" required></select>
                    @error('batch_id')<span class="errm">{{ $message }}</span>@enderror
                    <small>তারপর সেই বিভাগের ব্যাচ বাছুন</small></div>
                <div class="ff"><label for="course">কোর্স</label><input id="course" class="in" type="text" readonly><small>ব্যাচ অনুযায়ী স্বয়ংক্রিয়</small></div>
                @include('admin.partials.field', $f('admission_date', 'ভর্তির তারিখ', ['req' => true, 'type' => 'date']))
                @include('admin.partials.select', ['name' => 'admission_type', 'label' => 'ভর্তির ধরন', 'value' => $student->admission_type, 'options' => ['নতুন ভর্তি', 'পুনঃভর্তি', 'ব্যাচ পরিবর্তন']])
                @include('admin.partials.field', $f('reference', 'রেফারেন্স', ['ph' => 'যেমন: সরাসরি, বন্ধু, ফেসবুক']))
                @if ($editing)
                    @include('admin.partials.select', ['name' => 'status', 'label' => 'স্টুডেন্টের অবস্থা', 'req' => true, 'value' => $student->status, 'options' => collect(\App\Models\Student::STATUSES)->map(fn ($s) => $s[0])->all()])
                @endif
            </div>
            <div style="margin:0 24px 24px;padding:16px 20px;background:#F1F7EE;border-radius:12px;display:grid;grid-template-columns:repeat(auto-fit,minmax(min(170px,100%),1fr));gap:14px 20px">
                <div class="fld"><span>ক্লাস রুটিন</span><b id="i-routine">—</b></div>
                <div class="fld"><span>মেয়াদ</span><b id="i-term">—</b></div>
                <div class="fld"><span>শিক্ষক</span><b id="i-teacher">—</b></div>
                <div class="fld"><span>স্টুডেন্ট আইডি</span><b id="i-id">{{ $editing ? $student->student_code : '—' }}</b></div>
            </div>
        </section>

        <section id="s6" class="card">
            <div class="sh left"><span class="no">৬</span><h2>ফি ও পেমেন্ট</h2></div>
            <div class="fgrid">
                @if ($editing)
                    @include('admin.partials.field', $f('course_fee', 'কোর্স ফি (৳)', ['req' => true, 'mode' => 'numeric', 'id' => 'cfee']))
                    @include('admin.partials.field', $f('admission_fee', 'ভর্তি ফি (৳)', ['req' => true, 'mode' => 'numeric', 'id' => 'afee']))
                @else
                    <div class="ff"><label for="cfee">কোর্স ফি (৳)</label><input id="cfee" class="in" type="text" readonly><small>বিভাগ থেকে স্বয়ংক্রিয়</small></div>
                    <div class="ff"><label for="afee">ভর্তি ফি (৳)</label><input id="afee" class="in" type="text" readonly></div>
                @endif
                @include('admin.partials.field', $f('discount', 'ছাড় (৳)', ['mode' => 'numeric', 'ph' => '০']))
                <div class="ff"><label for="total">মোট প্রদেয় (৳)</label><input id="total" class="in" type="text" readonly></div>
                @unless ($editing)
                    @include('admin.partials.field', ['name' => 'first_payment', 'label' => 'প্রথম পেমেন্ট (৳)', 'value' => '', 'mode' => 'numeric', 'ph' => 'যেমন: ৩০০০', 'hint' => 'রসিদ স্বয়ংক্রিয়ভাবে তৈরি হবে'])
                    @include('admin.partials.select', ['name' => 'method', 'label' => 'পেমেন্টের মাধ্যম', 'value' => null, 'options' => $methods])
                @endunless
                @include('admin.partials.select', ['name' => 'installments', 'label' => 'কিস্তির সংখ্যা', 'value' => $student->installments, 'options' => [1 => 'এককালীন', 2 => '২টি', 3 => '৩টি', 4 => '৪টি', 5 => '৫টি', 6 => '৬টি']])
                @include('admin.partials.field', $f('next_installment_date', 'পরবর্তী কিস্তির তারিখ', ['type' => 'date']))
                @include('admin.partials.field', $f('next_installment_amount', 'পরবর্তী কিস্তির পরিমাণ (৳)', ['mode' => 'numeric']))
            </div>
        </section>

        <section id="s7" class="card">
            <div class="sh left"><span class="no">৭</span><h2>ডকুমেন্ট আপলোড</h2></div>
            <div class="fgrid">
                @include('admin.partials.upload', ['name' => 'photo', 'label' => 'পাসপোর্ট সাইজ ছবি', 'btn' => 'ছবি বাছুন', 'accept' => 'image/*'])
                @include('admin.partials.upload', ['name' => 'doc_nid', 'label' => 'জন্মনিবন্ধন / এনআইডি কপি', 'btn' => 'ফাইল বাছুন', 'accept' => '.jpg,.jpeg,.png,.pdf'])
                @include('admin.partials.upload', ['name' => 'doc_certificate[]', 'label' => 'শিক্ষাগত সনদের কপি', 'btn' => 'ফাইল বাছুন', 'accept' => '.jpg,.jpeg,.png,.pdf', 'multiple' => true])
                @include('admin.partials.upload', ['name' => 'doc_form', 'label' => 'স্বাক্ষরিত ভর্তি ফরম', 'btn' => 'ফাইল বাছুন', 'accept' => '.jpg,.jpeg,.png,.pdf'])
            </div>
            <p class="mute" style="margin:0;padding:0 24px 24px;font-size:14px">ছবি সর্বোচ্চ ২ MB, অন্যান্য ফাইল (JPG, PNG বা PDF) সর্বোচ্চ ৫ MB।</p>
        </section>

        <div class="card" style="padding:16px 24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
            <span class="mute" style="font-size:15px">{{ $editing ? 'পরিবর্তন সংরক্ষণ করলে প্রোফাইলে ফিরে যাবেন।' : 'জমা দেওয়ার পর রসিদ ও আইডি কার্ড প্রিন্ট করা যাবে।' }}</span>
            <div class="row">
                <a class="btn" href="{{ $editing ? route('admin.students.show', $student) : url()->previous() }}">বাতিল</a>
                <button class="btn p" type="submit">{{ $editing ? 'সংরক্ষণ করুন' : 'ভর্তি সম্পন্ন করুন' }}</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const data = @json($batchData);
    const editing = @json($editing);
    const selected = @json($selBatch);
    const dept = document.getElementById('dept');
    const batch = document.getElementById('batch');
    const bn = (s) => String(s).replace(/\d/g, (d) => '০১২৩৪৫৬৭৮৯'[d]);
    const ascii = (s) => String(s || '').replace(/[০-৯]/g, (d) => '০১২৩৪৫৬৭৮৯'.indexOf(d)).replace(/[^\d]/g, '');
    const money = (n) => bn(Number(n).toLocaleString('en-IN'));

    function fillBatches() {
        const d = data[dept.value];
        batch.innerHTML = '';
        (d ? d.batches : []).forEach((b) => {
            const o = document.createElement('option');
            o.value = b.id; o.textContent = b.label; o.selected = b.id === selected;
            batch.appendChild(o);
        });
        showInfo();
    }
    function showInfo() {
        const d = data[dept.value];
        const b = d && d.batches.find((x) => String(x.id) === batch.value);
        document.getElementById('course').value = d ? d.course : '';
        document.getElementById('i-routine').textContent = b ? b.routine : '—';
        document.getElementById('i-term').textContent = b ? b.term : '—';
        document.getElementById('i-teacher').textContent = b ? b.teacher : '—';
        if (!editing) {
            document.getElementById('i-id').textContent = b ? b.id_preview : '—';
            document.getElementById('cfee').value = d ? money(d.course_fee) : '';
            document.getElementById('afee').value = d ? money(d.admission_fee) : '';
        }
        total();
    }
    function total() {
        const d = data[dept.value];
        const c = editing ? +ascii(document.getElementById('cfee').value) : (d ? d.course_fee : 0);
        const a = editing ? +ascii(document.getElementById('afee').value) : (d ? d.admission_fee : 0);
        const disc = +ascii(document.getElementById('discount').value);
        document.getElementById('total').value = money(Math.max(0, c + a - disc));
    }
    dept.addEventListener('change', fillBatches);
    batch.addEventListener('change', showInfo);
    ['discount', 'cfee', 'afee'].forEach((id) => { const el = document.getElementById(id); if (el) el.addEventListener('input', total); });

    const same = document.getElementById('same');
    const perm = document.getElementById('perm');
    const togglePerm = () => { perm.hidden = same.checked; };
    same.addEventListener('change', togglePerm);
    togglePerm();

    document.querySelectorAll('input[type=file]').forEach((input) => {
        input.addEventListener('change', () => {
            const label = document.querySelector('label[for="' + input.id + '"]');
            if (label) label.textContent = input.files.length > 1 ? bn(input.files.length) + 'টি ফাইল বাছাই হয়েছে' : (input.files[0] ? input.files[0].name : label.dataset.text);
        });
    });
    fillBatches();
})();
</script>
@endpush
