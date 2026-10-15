<div class="scroll">
    <table class="tbl" style="min-width:960px">
        <thead><tr><th class="th">ব্যাচ</th>@if (! empty($showDept))<th class="th">বিভাগ</th>@endif<th class="th">ক্লাস রুটিন</th><th class="th">মেয়াদ</th><th class="th">শিক্ষক</th><th class="th">স্টুডেন্ট</th><th class="th">অবস্থা</th><th class="th"></th></tr></thead>
        <tbody>
        @forelse ($batches as $b)
            <tr>
                <td class="td tall"><a href="{{ route('admin.batches.show', $b) }}" style="text-decoration:none;display:flex;flex-direction:column;line-height:1.4"><b style="font-size:16px;color:#0E3A05">{{ $b->code }}</b><span style="color:#55635A;font-size:14px">{{ $b->name }}</span></a></td>
                @if (! empty($showDept))<td class="td tall">{{ $b->department->name }}</td>@endif
                <td class="td tall"><div style="display:flex;flex-direction:column;line-height:1.4"><b style="font-weight:600">{{ $b->days ?: '—' }}</b><span style="color:#55635A;font-size:14px">{{ $b->class_time }}</span></div></td>
                <td class="td tall"><div style="display:flex;flex-direction:column;line-height:1.4"><b style="font-weight:600">{{ bn_date($b->start_date) }}</b><span style="color:#55635A;font-size:14px">থেকে {{ bn_date($b->end_date) }}</span></div></td>
                <td class="td tall">{{ $b->teacher ?: '—' }}</td>
                <td class="td tall"><div style="display:flex;flex-direction:column;gap:6px;min-width:120px"><span style="font-weight:700">{{ bn($b->active_students_count) }} <span style="font-weight:500;color:#55635A">/ {{ bn($b->capacity) }}</span></span><span class="bar thin"><span style="width:{{ min(100, round($b->active_students_count / max(1, $b->capacity) * 100)) }}%"></span></span></div></td>
                <td class="td tall"><span class="badge {{ $b->statusTone() }}">{{ $b->statusLabel() }}</span></td>
                <td class="td tall" style="text-align:right"><a class="btn" href="{{ route('admin.batches.show', $b) }}">স্টুডেন্ট দেখুন @include('partials.icon', ['n' => 'chev', 'size' => 16])</a></td>
            </tr>
        @empty
            <tr><td class="td mute" colspan="8">{{ $empty ?? 'কোনো ব্যাচ নেই।' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
