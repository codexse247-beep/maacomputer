{{-- Month grid (Saturday first). Needs: $month (first day), $events, optional $classDay(Carbon): bool --}}
@php
    $start = $month->copy()->startOfMonth();
    $offset = ($start->dayOfWeek + 1) % 7; // Saturday = 0
    $days = $start->daysInMonth;
    $classDay = $classDay ?? fn ($d) => false;
@endphp
<style>
.mcal{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:4px}
.mcal .dh{font-size:13px;font-weight:700;color:#55635A;text-align:center;padding:6px 0}
.mcal .d{min-height:78px;border:1px solid #DFE5DB;border-radius:10px;padding:4px 6px;font-size:12px;display:flex;flex-direction:column;gap:3px;background:#fff;overflow:hidden}
.mcal .d.today{border-color:#176008;box-shadow:inset 0 0 0 1px #176008}
.mcal .d.cls{background:#F1F7EE}
.mcal .d.off{background:#FCEBEC}
.mcal .n{font-weight:700;font-size:14px;color:#14201A}
.mcal .ev{border-radius:5px;padding:1px 5px;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
@media (max-width:600px){.mcal .d{min-height:54px}.mcal .ev{font-size:0;height:6px;padding:0}}
</style>
<div class="mcal" role="grid" aria-label="{{ bn_date($start, false) }} মাসের ক্যালেন্ডার">
    @foreach (['শনি', 'রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র'] as $dn)<div class="dh" role="columnheader">{{ $dn }}</div>@endforeach
    @for ($i = 0; $i < $offset; $i++)<div></div>@endfor
    @for ($n = 1; $n <= $days; $n++)
        @php
            $day = $start->copy()->day($n);
            $today = $events->filter(fn ($e) => $e->covers($day));
            $off = $today->contains('type', 'holiday');
        @endphp
        <div class="d {{ $day->isToday() ? 'today' : '' }} {{ $off ? 'off' : ($classDay($day) ? 'cls' : '') }}" role="gridcell">
            <span class="n">{{ bn($n) }}</span>
            @foreach ($today as $e)<span class="ev" style="background:{{ $e->color() }}" title="{{ $e->typeLabel() }}: {{ $e->title }}">{{ $e->title }}</span>@endforeach
        </div>
    @endfor
</div>
