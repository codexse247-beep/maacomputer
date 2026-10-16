{{-- Grid view (like Google Drive): folders as tiles first, then files as cards with a preview. --}}
@php
    use App\Models\DriveItem;
    $folderItems = $items->where('is_folder', true);
    $fileItems = $items->where('is_folder', false);
    $hrefOf = fn ($it) => $trash ? null : ($it->is_folder ? route('admin.drive.index', ['folder' => $it->id]) : route('admin.drive.open', $it));
@endphp
<div class="dv-gwrap">
    <div class="dv-gtools">
        <label style="display:inline-flex;align-items:center;gap:8px;font-weight:600;font-size:14px;cursor:pointer"><input type="checkbox" id="sel-all">সব বাছুন</label>
        @unless ($view === 'recent' || $trash)
            <span class="mute" style="font-size:14px;display:inline-flex;align-items:center;gap:4px;flex-wrap:wrap">সাজান:
                @foreach (['name' => 'নাম', 'updated_at' => 'পরিবর্তন', 'size' => 'সাইজ'] as $col => $label)
                    <a href="{{ $sortUrl($col) }}" class="dv-chip {{ $sort === $col ? 'on' : '' }}">{{ $label }}{{ $arrow($col) }}</a>
                @endforeach
            </span>
        @endunless
    </div>

    @if ($folderItems->isNotEmpty())
        <h3 class="dv-gh">ফোল্ডার</h3>
        <div class="dv-fgrid">
            @foreach ($folderItems as $it)
                @php $href = $hrefOf($it); @endphp
                <div class="dv-tile" title="{{ $it->name }}">
                    <input type="checkbox" name="ids[]" value="{{ $it->id }}" form="bulk" class="sel dv-check" aria-label="{{ $it->name }} বাছুন">
                    @include('admin.drive.icon', ['px' => 28])
                    @if ($href)<a class="dv-tname dv-stretch" href="{{ $href }}">{{ $it->name }}</a>@else<span class="dv-tname">{{ $it->name }}</span>@endif
                    @if ($it->starred)@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => 'currentColor', 'style' => 'color:#C28A00;flex:none'])@endif
                    @include('admin.drive.menu')
                </div>
            @endforeach
        </div>
    @endif

    @if ($fileItems->isNotEmpty())
        <h3 class="dv-gh">ফাইল</h3>
        <div class="dv-grid">
            @foreach ($fileItems as $it)
                @php $href = $hrefOf($it); $kind = $it->kind(); @endphp
                <div class="dv-card" title="{{ $it->name }}">
                    <div class="dv-chead">
                        <input type="checkbox" name="ids[]" value="{{ $it->id }}" form="bulk" class="sel dv-check" aria-label="{{ $it->name }} বাছুন">
                        @include('admin.drive.icon', ['px' => 24, 'noThumb' => true])
                        @if ($href)<a class="dv-tname dv-stretch" href="{{ $href }}" target="_blank">{{ $it->name }}</a>@else<span class="dv-tname">{{ $it->name }}</span>@endif
                        @include('admin.drive.menu')
                    </div>
                    <div class="dv-prev" style="--k:{{ $kindColors[$kind] }}">
                        @if ($it->hasThumb())
                            <img src="{{ route('admin.drive.thumb', $it) }}" alt="" loading="lazy">
                        @else
                            <span class="dv-big">
                                @include('partials.icon', ['n' => $kindIcons[$kind] ?? 'file', 'size' => 46, 'w' => 1.6])
                                <b>{{ strtoupper($it->extension()) ?: 'FILE' }}</b>
                            </span>
                        @endif
                    </div>
                    <div class="dv-cfoot">
                        <span>{{ DriveItem::sizeText($it->size) }} · {{ bn(($trash ? $it->deleted_at : $it->updated_at)->format('d M Y')) }}</span>
                        <span style="display:inline-flex;gap:4px">
                            @if ($it->share_token)<span title="শেয়ার লিংক চালু" style="color:#1F5FA8;display:inline-flex">@include('partials.icon', ['n' => 'link', 'size' => 14])</span>@endif
                            @if ($it->starred)@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => 'currentColor', 'style' => 'color:#C28A00'])@endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
