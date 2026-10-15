<div class="card" style="max-width:640px;width:100%;padding:clamp(20px,4vw,32px);display:flex;flex-direction:column;gap:20px">
    <div style="display:flex;align-items:center;gap:14px;border-bottom:2px solid #176008;padding-bottom:16px">
        <img src="{{ logo_url() }}" alt="লোগো" style="width:56px;height:56px;border-radius:50%;flex:none">
        <div style="min-width:0"><b style="font-size:20px;color:#0E3A05">{{ setting('name') }}</b><div class="mute" style="font-size:14px">{{ setting('area') }} · {{ setting('phone') }}</div></div>
    </div>
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px"><b>রসিদ নং {{ $payment->receipt_no }}</b><span class="badge b-g">পরিশোধিত</span></div>
    <dl class="dl2">
        <dt>স্টুডেন্ট</dt><dd>{{ $student->name_bn }} ({{ $student->name_en }})</dd>
        <dt>আইডি</dt><dd>{{ $student->student_code }} · রোল {{ bn($student->roll) }}</dd>
        <dt>ব্যাচ</dt><dd>{{ $student->batch->department->name }} · {{ $student->batch->code }}</dd>
        <dt>পেমেন্টের ধরন</dt><dd>{{ $payment->item }}</dd>
        <dt>তারিখ</dt><dd>{{ bn_date($payment->paid_on) }}</dd>
        <dt>পরিশোধ পদ্ধতি</dt><dd>{{ $payment->method }}</dd>
    </dl>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px;border-radius:12px;background:#F1F7EE"><b>পরিশোধিত অর্থ</b><b style="font-size:22px;color:#176008">{{ taka($payment->amount) }}</b></div>
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;color:#55635A;font-size:14px">
        <span>মোট প্রদেয় {{ taka($student->totalPayable()) }} · মোট পরিশোধিত {{ taka($student->totalPaid()) }} · অবশিষ্ট বকেয়া: <b style="color:#B00E28">{{ taka($student->totalDue()) }}</b></span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:28px;color:#55635A;font-size:14px">
        <span>গ্রহণকারী: {{ $payment->receiver?->name ?? 'অফিস' }}</span>
        <span style="border-top:1px solid #14201A;padding-top:6px;min-width:170px;text-align:center;color:#14201A">কর্তৃপক্ষের স্বাক্ষর</span>
    </div>
</div>
