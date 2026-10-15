<div class="idcard">
    <div class="h"><img src="{{ logo_url() }}" alt=""><b style="line-height:1.3">{{ setting('name') }}</b></div>
    <div class="b">
        @if ($s->photoUrl())<img class="ph" src="{{ $s->photoUrl() }}" alt="{{ $s->name_bn }} এর ছবি">@else<div class="ph">{{ initial($s->name_bn) }}</div>@endif
        <b style="font-size:20px;color:#0E3A05;margin-top:4px">{{ $s->name_bn }}</b>
        <span style="font-weight:700;color:#C8102E">{{ $s->student_code }}</span>
        <span class="mute">{{ $s->batch->department->name }} · {{ $s->batch->code }}</span>
        <span style="font-size:14px">রোল {{ bn($s->roll) }} · {{ bn($s->phone) }}@if ($s->blood_group) · রক্ত {{ $s->blood_group }}@endif</span>
    </div>
    <div class="f">মেয়াদ: {{ bn_date($s->batch->end_date) }} · {{ setting('domain') }}</div>
</div>
