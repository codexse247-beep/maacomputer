@extends('layouts.admin')

@section('title', 'ফলাফল')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ফলাফল</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">ফলাফল</h1>
        <p class="lead">পরীক্ষার নম্বর তোলা ও প্রকাশ। ৪০% বা তার বেশি পেলে উত্তীর্ণ।</p>
    </div>
    @if ($batch && $exam !== '')<a class="btn" href="{{ route('admin.results.export', [$batch, 'exam' => $exam]) }}">শিট ডাউনলোড</a>@endif
</div>

<form method="GET" class="card" style="padding:20px;display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end">
    <div class="ff" style="flex:1 1 220px">
        <label for="rb">ব্যাচ</label>
        <select class="in" id="rb" name="batch" onchange="this.form.exam.value='';this.form.submit()">
            @foreach ($batches as $b)<option value="{{ $b->id }}" @selected($batch && $batch->id === $b->id)>{{ $b->code }} · {{ $b->name }} ({{ $b->statusLabel() }})</option>@endforeach
        </select>
    </div>
    <div class="ff" style="flex:1 1 260px">
        <label for="re">পরীক্ষা</label>
        <select class="in" id="re" name="exam" onchange="this.form.submit()">
            @foreach ($exams as $e)<option value="{{ $e }}" @selected($exam === $e)>{{ $e }}</option>@endforeach
            <option value="" @selected($exam === '')>+ নতুন পরীক্ষা</option>
        </select>
    </div>
</form>

@if ($batch)
@if ($exam !== '')
<div class="stats">
    <div class="card statc"><span class="k">গড় নম্বর</span><span class="v">{{ $average === null ? '—' : bn($average) }}</span><span class="s">{{ bn($students->count()) }} জনের মধ্যে · পূর্ণমান {{ bn($max) }}</span></div>
    <div class="card statc"><span class="k">সর্বোচ্চ</span><span class="v">{{ $best ? bn($best->score) : '—' }}</span><span class="s">{{ $best ? $students->firstWhere('id', $best->student_id)?->name_bn : '' }}</span></div>
    <div class="card statc"><span class="k">পাসের হার</span><span class="v">{{ $passRate === null ? '—' : bn($passRate).'%' }}</span><span class="s">{{ bn($passCount) }} জন পাস</span></div>
</div>
@endif

<form method="POST" action="{{ route('admin.results.store', $batch) }}" class="card" style="overflow:hidden">
    @csrf
    <div class="fgrid" style="padding-top:20px">
        @include('admin.partials.field', ['name' => 'title', 'label' => 'পরীক্ষার নাম', 'req' => true, 'value' => $exam, 'ph' => 'যেমন: মডিউল টেস্ট ১ — ফটোশপ'])
        @include('admin.partials.field', ['name' => 'max_score', 'label' => 'পূর্ণমান', 'req' => true, 'value' => $max, 'mode' => 'numeric', 'id' => 'rmax'])
        @include('admin.partials.field', ['name' => 'held_on', 'label' => 'পরীক্ষার তারিখ', 'type' => 'date', 'value' => $heldOn ?? now()])
    </div>
    <div class="scroll">
        <table class="tbl" style="min-width:760px">
            <thead><tr><th class="th">স্টুডেন্ট</th><th class="th" style="width:160px">প্রাপ্ত নম্বর</th><th class="th">গ্রেড</th><th class="th">মন্তব্য</th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                @php $a = $existing[$s->id] ?? null; [$g, $gt] = $a ? $a->grade() : ['—', 'b-n']; @endphp
                <tr>
                    <td class="td" style="height:68px"><b>{{ $s->name_bn }}</b><br><span class="mute" style="font-size:14px">{{ $s->student_code }}</span></td>
                    <td class="td" style="height:68px"><label class="sr" for="m{{ $s->id }}">{{ $s->name_bn }} এর নম্বর</label><input class="in mark" id="m{{ $s->id }}" name="marks[{{ $s->id }}]" type="text" inputmode="numeric" value="{{ old('marks.'.$s->id, $a?->score) }}" style="width:120px"></td>
                    <td class="td" style="height:68px"><span class="badge {{ $gt }}" data-grade="m{{ $s->id }}">{{ $g }}</span></td>
                    <td class="td" style="height:68px"><label class="sr" for="r{{ $s->id }}">{{ $s->name_bn }} এর মন্তব্য</label><input class="in" id="r{{ $s->id }}" name="remarks[{{ $s->id }}]" type="text" value="{{ old('remarks.'.$s->id, $a?->remark) }}" placeholder="ঐচ্ছিক"></td>
                </tr>
            @empty
                <tr><td class="td mute" colspan="4">এই ব্যাচে কোনো স্টুডেন্ট নেই।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:16px;border-top:1px solid #DFE5DB">
        <span class="mute" style="font-size:14px">{{ $exam === '' ? 'নতুন পরীক্ষা' : ($published ? 'প্রকাশিত — স্টুডেন্টরা পোর্টালে দেখতে পাচ্ছে' : 'খসড়া — এখনো প্রকাশ হয়নি') }}</span>
        <div class="row">
            <button class="btn" name="action" value="draft">খসড়া সংরক্ষণ</button>
            <button class="btn p" name="action" value="publish">ফলাফল প্রকাশ করুন</button>
        </div>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const max = document.getElementById('rmax');
    const ascii = (s) => String(s || '').replace(/[০-৯]/g, (d) => '০১২৩৪৫৬৭৮৯'.indexOf(d)).trim();
    const grade = (p) => p >= 80 ? ['A+', 'b-g'] : p >= 70 ? ['A', 'b-g'] : p >= 60 ? ['A-', 'b-b'] : p >= 50 ? ['B', 'b-b'] : p >= 40 ? ['C', 'b-a'] : ['F', 'b-r'];
    function update(input) {
        const badge = document.querySelector('[data-grade="' + input.id + '"]');
        const v = ascii(input.value), m = +ascii(max.value);
        if (!badge) return;
        if (v === '' || !m) { badge.textContent = '—'; badge.className = 'badge b-n'; return; }
        const [g, t] = grade(+v / m * 100);
        badge.textContent = g; badge.className = 'badge ' + t;
    }
    document.querySelectorAll('.mark').forEach((i) => i.addEventListener('input', () => update(i)));
    if (max) max.addEventListener('input', () => document.querySelectorAll('.mark').forEach(update));
})();
</script>
@endpush
