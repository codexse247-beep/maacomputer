<div style="display:flex;flex-direction:column;margin-top:36px;border:1px solid #DFE5DB;border-radius:18px;overflow:hidden">
    @forelse ($notices as $n)
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:16px 24px;padding:22px 28px;background:#fff;{{ $loop->last ? '' : 'border-bottom:1px solid #DFE5DB' }}">
        <span style="min-width:96px;padding:8px 12px;border-radius:10px;background:#E3F0DD;color:#176008;font-weight:700;font-size:15px;text-align:center">{{ bn_date($n->published_on, false) }}</span>
        <span style="flex:1 1 280px;display:flex;flex-direction:column">
            <span style="font-weight:600;font-size:18px;color:#0E3A05">{{ $n->title }}</span>
            @if ($n->body)<span style="font-size:15px;color:#55635A;white-space:pre-line">{{ $n->body }}</span>@endif
        </span>
        <span class="badge {{ $n->tagTone() }}" style="font-size:14px">{{ __($n->tagLabel()) }}</span>
    </div>
    @empty
    <div style="padding:28px;background:#fff;color:#55635A">{{ __('এখনো কোনো নোটিশ নেই।') }}</div>
    @endforelse
</div>
