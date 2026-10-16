{{-- Small coloured type icon (or the photo itself) for one item. Needs $it; optional $px. --}}
@php $kind = $it->kind(); $px = $px ?? 36; @endphp
<span class="dv-ic" style="width:{{ $px }}px;height:{{ $px }}px;background:{{ $kindColors[$kind] }}1A;color:{{ $kindColors[$kind] }}">
    @if ($it->hasThumb() && empty($noThumb))<img src="{{ route('admin.drive.thumb', $it) }}" alt="" loading="lazy" style="width:{{ $px }}px;height:{{ $px }}px">
    @elseif (isset($kindIcons[$kind]))@include('partials.icon', ['n' => $kindIcons[$kind], 'size' => (int) round($px * .56), 'fill' => $kind === 'folder' ? 'currentColor' : 'none'])
    @else<b style="font-size:{{ $px >= 30 ? 10 : 8 }}px;letter-spacing:.3px">{{ strtoupper(substr($it->extension(), 0, 4)) ?: 'FILE' }}</b>@endif
</span>
