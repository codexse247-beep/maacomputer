@php
$steps = [
    1 => ['বিভাগ', 'বিভাগ ও কোর্স ফি'],
    2 => ['ব্যাচ', 'রুটিন, কোর্স, শিক্ষক'],
    3 => ['স্টুডেন্ট', 'রোল, ফি, উপস্থিতি'],
    4 => ['স্টুডেন্টের তথ্য', 'সম্পূর্ণ প্রোফাইল ও রেকর্ড'],
];
@endphp
<div class="card stepper" aria-label="ধাপ">
    @foreach ($steps as $i => [$title, $hint])
        <div class="{{ $i === $at ? 'on' : '' }}" @if ($i === $at) aria-current="step" @endif><i>{{ bn($i) }}</i><span><b>{{ $title }}</b><small>{{ $i === $at ? 'আপনি এখানে' : $hint }}</small></span></div>
    @endforeach
</div>
