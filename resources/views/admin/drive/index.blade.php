@extends('layouts.admin')

@section('title', 'ড্রাইভ')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>ড্রাইভ</strong>@endsection

@php
    use App\Models\DriveItem;
    $trash = $view === 'trash';
    $title = match (true) {
        $q !== '' => 'খোঁজার ফল: “'.$q.'”',
        $view === 'recent' => 'সাম্প্রতিক',
        $view === 'starred' => 'তারকা দেওয়া',
        $trash => 'ট্র্যাশ',
        default => $folder?->name ?? 'আমার ড্রাইভ',
    };
    $canUpload = ! $view && $q === '';
    $kindColors = ['folder' => '#8A5300', 'image' => '#B00E28', 'video' => '#B00E28', 'audio' => '#7A3FB0', 'pdf' => '#C8102E', 'doc' => '#1F5FA8', 'sheet' => '#176008', 'slide' => '#C25A00', 'zip' => '#55635A', 'design' => '#1F5FA8', 'file' => '#55635A'];
    $kindIcons = ['folder' => 'folder', 'image' => 'image', 'video' => 'video', 'audio' => 'play', 'file' => 'file'];
    $sortUrl = fn ($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => $sort === $col && $dir === 'asc' ? 'desc' : 'asc']);
    $arrow = fn ($col) => $sort === $col ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
    $total = $used + $free;
@endphp

@push('head')
<style>
.dv{display:flex;gap:24px;align-items:flex-start}
.dv-side{flex:0 0 230px;display:flex;flex-direction:column;gap:6px;position:sticky;top:16px}
.dv-side a{display:flex;align-items:center;gap:10px;height:42px;padding:0 14px;border-radius:99px;text-decoration:none;color:#14201A;font-weight:600;font-size:15px}
.dv-side a:hover{background:#F1F7EE}
.dv-side a.on{background:#E3F0DD;color:#0E3A05}
.dv-side .cnt{margin-left:auto;font-size:12px;color:#55635A}
.dv-main{flex:1;min-width:0;overflow:visible}
.dv-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #DFE5DB}
.dv-crumbs{display:flex;flex-wrap:wrap;align-items:center;gap:2px;font-size:19px;font-weight:700;color:#0E3A05}
.dv-crumbs a{color:#55635A;text-decoration:none;padding:4px 8px;border-radius:8px}
.dv-crumbs a:hover{background:#F1F7EE;color:#0E3A05}
.dv-crumbs span.cur{padding:4px 8px}
.dv-ic{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;flex:none;overflow:hidden}
.dv-ic img{width:36px;height:36px;object-fit:cover}
.dv-name{display:flex;align-items:center;gap:12px;min-width:0;color:#14201A;text-decoration:none;font-weight:600}
.dv-name span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:46ch}
a.dv-name:hover span{text-decoration:underline}
.dv-star{color:#C28A00;flex:none}
.dv-menu{position:relative;display:inline-block}
.dv-menu>summary{list-style:none;display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;cursor:pointer;color:#55635A}
.dv-menu>summary::-webkit-details-marker{display:none}
.dv-menu>summary:hover,.dv-menu[open]>summary{background:#EBEFE8}
.dv-new>summary:hover,.dv-new[open]>summary{background:#0E3A05}
.dv-rowmenu .dv-pop{position:fixed;right:auto;max-height:calc(100vh - 16px);overflow-y:auto}
.dv-pop{position:absolute;right:0;top:40px;z-index:30;min-width:210px;background:#fff;border:1px solid #DFE5DB;border-radius:12px;box-shadow:0 10px 30px rgba(14,58,5,.16);padding:6px;display:flex;flex-direction:column}
.dv-pop a,.dv-pop button{display:flex;align-items:center;gap:10px;height:40px;padding:0 12px;border:0;background:none;border-radius:8px;font:inherit;font-size:14px;font-weight:600;color:#14201A;text-decoration:none;cursor:pointer;text-align:left;width:100%}
.dv-pop a:hover,.dv-pop button:hover{background:#F1F7EE}
.dv-pop .dng{color:#B00E28}
.dv-pop form{margin:0}
.dv-sel{display:none;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 20px;background:#E3F0DD;border-bottom:1px solid #B7D3AB}
.dv-sel.on{display:flex}
.dv-drop{position:fixed;inset:0;z-index:60;background:rgba(23,96,8,.12);border:4px dashed #176008;display:none;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:#0E3A05;pointer-events:none}
.dv-drop.on{display:flex}
.dv-up{position:fixed;right:20px;bottom:20px;z-index:70;width:min(380px,calc(100vw - 32px));background:#fff;border:1px solid #DFE5DB;border-radius:14px;box-shadow:0 12px 36px rgba(14,58,5,.22);display:none;flex-direction:column;max-height:60vh}
.dv-up.on{display:flex}
.dv-up header{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 16px;background:#0E3A05;color:#fff;border-radius:14px 14px 0 0;font-weight:700}
.dv-up ul{list-style:none;margin:0;padding:6px 0;overflow:auto}
.dv-up li{padding:8px 16px;display:flex;flex-direction:column;gap:5px;font-size:14px}
.dv-up .row{display:flex;justify-content:space-between;gap:10px}
.dv-up .row b{font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dv-up progress{width:100%;height:6px;accent-color:#176008}
.dv-up .bad{color:#B00E28;font-weight:600}
.dv-meter{height:8px;border-radius:99px;background:#EBEFE8;overflow:hidden}
.dv-meter i{display:block;height:100%;background:#176008}
dialog.dv-dlg{border:0;border-radius:16px;padding:24px;width:min(440px,calc(100vw - 32px));box-shadow:0 20px 50px rgba(0,0,0,.25)}
dialog.dv-dlg::backdrop{background:rgba(20,32,26,.45)}
dialog.dv-dlg h2{margin:0 0 14px;font-size:20px;color:#0E3A05}
dialog.dv-dlg .acts{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
.dv-seg{display:inline-flex;border:1px solid #C9D2C5;border-radius:99px;overflow:hidden;height:40px}
.dv-seg a{display:inline-flex;align-items:center;justify-content:center;width:44px;color:#55635A}
.dv-seg a+a{border-left:1px solid #C9D2C5}
.dv-seg a.on{background:#E3F0DD;color:#0E3A05}
.dv-gwrap{padding:14px 20px 24px}
.dv-gtools{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px}
.dv-chip{display:inline-flex;align-items:center;height:30px;padding:0 12px;border-radius:99px;border:1px solid #DFE5DB;color:#14201A;text-decoration:none;font-weight:600;font-size:13px}
.dv-chip.on{background:#E3F0DD;border-color:#B7D3AB;color:#0E3A05}
.dv-gh{margin:18px 0 10px;font-size:15px;font-weight:700;color:#55635A}
.dv-fgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.dv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.dv-tile,.dv-card{position:relative;background:#F1F4EF;border:1px solid transparent;border-radius:14px;transition:background .12s,border-color .12s}
.dv-tile{display:flex;align-items:center;gap:10px;height:56px;padding:0 4px 0 12px}
.dv-tile:hover,.dv-card:hover{background:#E6ECE2}
.dv-tile:has(.sel:checked),.dv-card:has(.sel:checked){background:#D6E9CD;border-color:#176008}
.dv-tname{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;font-size:14px;color:#14201A;text-decoration:none}
.dv-stretch::after{content:'';position:absolute;inset:0;border-radius:14px}
.dv-tile .dv-menu,.dv-card .dv-menu,.dv-check{position:relative;z-index:2}
.dv-check{width:18px;height:18px;margin:0;flex:none;accent-color:#176008;opacity:0;cursor:pointer}
.dv-tile:hover .dv-check,.dv-card:hover .dv-check,.dv-check:checked,.dv-check:focus-visible,.dv-selecting .dv-check{opacity:1}
.dv-tile .dv-check{position:absolute;left:17px;top:19px}
.dv-tile:hover>.dv-ic,.dv-tile:has(.sel:checked)>.dv-ic,.dv-selecting .dv-tile>.dv-ic{visibility:hidden}
.dv-card{display:flex;flex-direction:column;padding:6px 6px 10px}
.dv-chead{display:flex;align-items:center;gap:8px;height:42px;padding-left:4px}
.dv-chead .dv-check{position:absolute;left:13px;top:18px}
.dv-card:hover .dv-chead>.dv-ic,.dv-card:has(.sel:checked) .dv-chead>.dv-ic,.dv-selecting .dv-chead>.dv-ic{visibility:hidden}
.dv-prev{aspect-ratio:4/3;border-radius:10px;background:#fff;overflow:hidden;display:flex;align-items:center;justify-content:center}
.dv-prev img{width:100%;height:100%;object-fit:cover;display:block}
.dv-big{display:flex;flex-direction:column;align-items:center;gap:6px;color:var(--k)}
.dv-big b{font-size:12px;letter-spacing:.5px}
.dv-cfoot{display:flex;justify-content:space-between;align-items:center;gap:6px;padding:8px 6px 0;font-size:12px;color:#55635A}
@media (hover: none){.dv-check{opacity:1}.dv-card .dv-chead>.dv-ic,.dv-tile>.dv-ic{visibility:hidden}}
@media (max-width: 560px){.dv-grid,.dv-fgrid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.dv-gwrap{padding:12px}}
.dv-empty{padding:56px 24px;text-align:center;color:#55635A;display:flex;flex-direction:column;align-items:center;gap:10px}
@media (max-width: 960px){
  .dv{flex-direction:column}
  .dv-side{position:static;flex:none;width:100%;flex-direction:row;flex-wrap:wrap}
  .dv-side a{height:38px;padding:0 12px;font-size:14px}
  .dv-side .storage{width:100%}
  .dv-hide{display:none}
}
</style>
@endpush

@section('content')
<div>
    <h1 class="h1">ড্রাইভ</h1>
    <p class="lead">অফিসের ফাইল রাখুন — আপলোড, ফোল্ডার, ডাউনলোড ও শেয়ার লিংক। ফাইল টেনে এনে এই পাতায় ছাড়লেও আপলোড হয়।</p>
</div>

<div class="dv">
    <aside class="dv-side">
        @if ($canUpload)
            <details class="dv-menu dv-new" style="margin-bottom:8px">
                <summary class="btn p" style="width:auto;height:48px;border-radius:14px;padding:0 22px;color:#fff">@include('partials.icon', ['n' => 'plus'])নতুন</summary>
                <div class="dv-pop" style="left:0;right:auto;top:54px">
                    <button type="button" data-open="dlg-folder">@include('partials.icon', ['n' => 'folder'])নতুন ফোল্ডার</button>
                    <button type="button" onclick="document.getElementById('pick-files').click()">@include('partials.icon', ['n' => 'upload'])ফাইল আপলোড</button>
                    <button type="button" onclick="document.getElementById('pick-folder').click()">@include('partials.icon', ['n' => 'folder'])ফোল্ডার আপলোড</button>
                </div>
            </details>
        @endif
        <a href="{{ route('admin.drive.index') }}" class="{{ ! $view && $q === '' ? 'on' : '' }}">@include('partials.icon', ['n' => 'drive'])আমার ড্রাইভ</a>
        <a href="{{ route('admin.drive.index', ['view' => 'recent']) }}" class="{{ $view === 'recent' ? 'on' : '' }}">@include('partials.icon', ['n' => 'clock'])সাম্প্রতিক</a>
        <a href="{{ route('admin.drive.index', ['view' => 'starred']) }}" class="{{ $view === 'starred' ? 'on' : '' }}">@include('partials.icon', ['n' => 'star'])তারকা দেওয়া</a>
        <a href="{{ route('admin.drive.index', ['view' => 'trash']) }}" class="{{ $trash ? 'on' : '' }}">@include('partials.icon', ['n' => 'trash'])ট্র্যাশ @if ($trashCount)<span class="cnt">{{ bn($trashCount) }}</span>@endif</a>
        <div class="storage" style="padding:14px;display:flex;flex-direction:column;gap:8px;font-size:13px">
            <div class="dv-meter" role="img" aria-label="জায়গা"><i style="width:{{ $total ? max(1, round($used / $total * 100)) : 0 }}%"></i></div>
            <span class="mute">ড্রাইভে {{ DriveItem::sizeText($used) }} · হার্ডডিস্কে খালি {{ DriveItem::sizeText($free) }}</span>
        </div>
    </aside>

    <div class="card dv-main">
        <div class="dv-bar">
            <nav class="dv-crumbs" aria-label="ফোল্ডার">
                @if (! $view && $q === '')
                    @if ($folder)<a href="{{ route('admin.drive.index') }}">আমার ড্রাইভ</a>@else<span class="cur">আমার ড্রাইভ</span>@endif
                    @foreach ($crumbs as $c)
                        @include('partials.icon', ['n' => 'chev', 'size' => 16])
                        @if ($loop->last)<span class="cur">{{ $c->name }}</span>@else<a href="{{ route('admin.drive.index', ['folder' => $c->id]) }}">{{ $c->name }}</a>@endif
                    @endforeach
                @else
                    <span class="cur">{{ $title }}</span>
                @endif
            </nav>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <form method="GET" action="{{ route('admin.drive.index') }}" role="search" style="display:flex;gap:6px">
                    <input class="in" type="search" name="q" value="{{ $q }}" placeholder="ড্রাইভে খুঁজুন" aria-label="ড্রাইভে খুঁজুন" style="width:200px;height:40px">
                    <button class="btn sm" style="height:40px" aria-label="খুঁজুন">@include('partials.icon', ['n' => 'search', 'size' => 18])</button>
                </form>
                <span class="dv-seg" role="group" aria-label="দেখার ধরন">
                    <a href="{{ request()->fullUrlWithQuery(['layout' => 'list']) }}" class="{{ $layout === 'list' ? 'on' : '' }}" title="তালিকা" aria-label="তালিকা আকারে" @if ($layout === 'list') aria-current="true" @endif>@include('partials.icon', ['n' => 'list', 'size' => 18])</a>
                    <a href="{{ request()->fullUrlWithQuery(['layout' => 'grid']) }}" class="{{ $layout === 'grid' ? 'on' : '' }}" title="গ্রিড" aria-label="গ্রিড আকারে" @if ($layout === 'grid') aria-current="true" @endif>@include('partials.icon', ['n' => 'grid', 'size' => 18])</a>
                </span>
                @if ($canUpload)
                    <button type="button" class="btn sm" style="height:40px" data-open="dlg-folder">@include('partials.icon', ['n' => 'folder', 'size' => 18])ফোল্ডার</button>
                    <button type="button" class="btn sm p" style="height:40px" onclick="document.getElementById('pick-files').click()">@include('partials.icon', ['n' => 'upload', 'size' => 18])আপলোড</button>
                @endif
                @if ($trash && $items->isNotEmpty())
                    <form method="POST" action="{{ route('admin.drive.empty') }}" onsubmit="return confirm('ট্র্যাশের সব কিছু চিরতরে মুছে যাবে। নিশ্চিত?')">@csrf @method('DELETE')<button class="btn sm d" style="height:40px">ট্র্যাশ খালি করুন</button></form>
                @endif
            </div>
        </div>

        @if ($trash)
            <div style="padding:10px 20px;background:#FFF3DB;color:#8A5300;font-size:14px;font-weight:600;border-bottom:1px solid #F1DDB0">ট্র্যাশের জিনিস ৩০ দিন পর নিজে থেকে চিরতরে মুছে যায়।</div>
        @endif

        {{-- Shown while some rows are ticked --}}
        <form method="POST" id="bulk" class="dv-sel" aria-live="polite">
            @csrf
            <b id="sel-count" style="margin-right:8px"></b>
            @if ($trash)
                <button class="btn sm" formaction="{{ route('admin.drive.restore') }}">@include('partials.icon', ['n' => 'restore', 'size' => 18])ফিরিয়ে আনুন</button>
                <button class="btn sm d" formaction="{{ route('admin.drive.destroy') }}" name="_method" value="DELETE" onclick="return confirm('চিরতরে মুছে যাবে, আর ফেরানো যাবে না। নিশ্চিত?')">চিরতরে মুছুন</button>
            @else
                <button class="btn sm" formaction="{{ route('admin.drive.zip') }}" data-download>@include('partials.icon', ['n' => 'download', 'size' => 18])ডাউনলোড (ZIP)</button>
                <button class="btn sm" type="button" data-move-selected>@include('partials.icon', ['n' => 'move', 'size' => 18])সরান</button>
                <button class="btn sm d" formaction="{{ route('admin.drive.trash') }}">@include('partials.icon', ['n' => 'trash', 'size' => 18])ট্র্যাশে ফেলুন</button>
            @endif
            <button class="el" type="button" id="sel-clear" style="height:36px">বাতিল</button>
        </form>

        @if ($items->isEmpty())
            <div class="dv-empty">
                @include('partials.icon', ['n' => $trash ? 'trash' : 'folder', 'size' => 56, 'w' => 1.4, 'color' => '#9DBB93'])
                @if ($trash) ট্র্যাশ খালি।
                @elseif ($q !== '') “{{ $q }}” নামে কিছু পাওয়া যায়নি।
                @elseif ($view) এখানে এখনো কিছু নেই।
                @else
                    <b style="color:#14201A;font-size:17px">এই ফোল্ডার খালি</b>
                    <span>ফাইল টেনে এনে এখানে ছাড়ুন, অথবা “আপলোড” চাপুন।</span>
                @endif
            </div>
        @elseif ($layout === 'grid')
            @include('admin.drive.grid')
        @else
            <div class="scroll" style="overflow-y:visible">
                <table class="tbl">
                    <thead><tr>
                        <th class="th" style="width:44px;padding-right:0"><input type="checkbox" id="sel-all" aria-label="সব বাছুন"></th>
                        <th class="th"><a href="{{ $sortUrl('name') }}" style="color:inherit;text-decoration:none">নাম{{ $arrow('name') }}</a></th>
                        <th class="th dv-hide">{{ $trash ? 'ট্র্যাশে ফেলার তারিখ' : '' }}@unless ($trash)<a href="{{ $sortUrl('updated_at') }}" style="color:inherit;text-decoration:none">পরিবর্তন{{ $arrow('updated_at') }}</a>@endunless</th>
                        <th class="th dv-hide">মালিক</th>
                        <th class="th"><a href="{{ $sortUrl('size') }}" style="color:inherit;text-decoration:none">সাইজ{{ $arrow('size') }}</a></th>
                        <th class="th" style="width:60px"><span class="vh">কাজ</span></th>
                    </tr></thead>
                    <tbody>
                    @foreach ($items as $it)
                        @php $href = $trash ? null : ($it->is_folder ? route('admin.drive.index', ['folder' => $it->id]) : route('admin.drive.open', $it)); @endphp
                        <tr>
                            <td class="td" style="padding-right:0"><input type="checkbox" name="ids[]" value="{{ $it->id }}" form="bulk" class="sel" aria-label="{{ $it->name }} বাছুন"></td>
                            <td class="td" style="max-width:0;width:60%">
                                <{{ $href ? 'a' : 'span' }} class="dv-name" @if ($href) href="{{ $href }}" @if (! $it->is_folder) target="_blank" @endif @endif title="{{ $it->name }}">
                                    @include('admin.drive.icon')
                                    <span>{{ $it->name }}</span>
                                    @if ($it->starred)@include('partials.icon', ['n' => 'star', 'size' => 15, 'fill' => 'currentColor', 'style' => 'color:#C28A00;flex:none'])@endif
                                    @if ($it->share_token)<span title="শেয়ার লিংক চালু" style="flex:none;color:#1F5FA8;display:inline-flex">@include('partials.icon', ['n' => 'link', 'size' => 15])</span>@endif
                                </{{ $href ? 'a' : 'span' }}>
                            </td>
                            <td class="td dv-hide mute" style="white-space:nowrap;font-size:14px">{{ bn(($trash ? $it->deleted_at : $it->updated_at)->format('d M Y')) }}</td>
                            <td class="td dv-hide mute" style="white-space:nowrap;font-size:14px">{{ $it->user?->name ?? '—' }}</td>
                            <td class="td mute" style="white-space:nowrap;font-size:14px">{{ $it->is_folder ? '—' : DriveItem::sizeText($it->size) }}</td>
                            <td class="td" style="text-align:right">@include('admin.drive.menu')</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Hidden pickers used by the buttons --}}
<input type="file" id="pick-files" multiple hidden>
<input type="file" id="pick-folder" webkitdirectory multiple hidden>

<dialog class="dv-dlg" id="dlg-folder">
    <form method="POST" action="{{ route('admin.drive.folders.store') }}">
        @csrf
        <h2>নতুন ফোল্ডার</h2>
        <input type="hidden" name="parent_id" value="{{ $folder?->id }}">
        <input class="in" name="name" value="নতুন ফোল্ডার" required maxlength="200" aria-label="ফোল্ডারের নাম" autofocus>
        <div class="acts"><button type="button" class="btn" data-close>বাতিল</button><button class="btn p">তৈরি করুন</button></div>
    </form>
</dialog>

<dialog class="dv-dlg" id="dlg-rename">
    <form method="POST">
        @csrf @method('PUT')
        <h2>নাম বদলান</h2>
        <input class="in" name="name" required maxlength="200" aria-label="নতুন নাম">
        <div class="acts"><button type="button" class="btn" data-close>বাতিল</button><button class="btn p">ঠিক আছে</button></div>
    </form>
</dialog>

<dialog class="dv-dlg" id="dlg-move">
    <form method="POST" action="{{ route('admin.drive.move') }}">
        @csrf
        <h2>কোথায় সরাবেন?</h2>
        <div id="move-ids"></div>
        <select class="in" name="target" aria-label="ফোল্ডার">
            <option value="">আমার ড্রাইভ (সবার উপরে)</option>
            @foreach ($folders as $id => $path)<option value="{{ $id }}">{{ $path }}</option>@endforeach
        </select>
        <div class="acts"><button type="button" class="btn" data-close>বাতিল</button><button class="btn p">সরান</button></div>
    </form>
</dialog>

<div class="dv-drop" id="drop">ফাইল এখানে ছাড়ুন — {{ $folder?->name ?? 'আমার ড্রাইভ' }}-এ আপলোড হবে</div>

<section class="dv-up" id="up" aria-live="polite">
    <header><span id="up-title">আপলোড হচ্ছে…</span><button type="button" class="el" id="up-close" style="color:#fff;height:28px" hidden>বন্ধ করুন</button></header>
    <ul id="up-list"></ul>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const CSRF = @json(csrf_token());
    const UPLOAD_URL = @json(route('admin.drive.upload'));
    const FOLDER = @json($folder?->id);
    const CAN_UPLOAD = @json($canUpload);
    const CHUNK = {{ \App\Http\Controllers\Admin\DriveController::CHUNK }};
    const $ = (s) => document.querySelector(s);

    // ---- dialogs and menus ----
    document.querySelectorAll('[data-open]').forEach((b) => b.addEventListener('click', () => { closeMenus(); $('#' + b.dataset.open).showModal(); }));
    document.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => b.closest('dialog').close()));
    const closeMenus = (except) => document.querySelectorAll('details.dv-menu[open]').forEach((d) => d !== except && d.removeAttribute('open'));
    document.addEventListener('click', (e) => closeMenus(e.target.closest('details.dv-menu')));

    // Row menus float above the page (the table's scroll box would cut them off) and open upwards near the bottom.
    document.querySelectorAll('details.dv-rowmenu').forEach((d) => d.addEventListener('toggle', () => {
        if (!d.open) return;
        const pop = d.querySelector('.dv-pop'), r = d.querySelector('summary').getBoundingClientRect();
        pop.style.visibility = 'hidden'; pop.style.top = '0px'; pop.style.left = '0px';
        const h = pop.offsetHeight, w = pop.offsetWidth, below = window.innerHeight - r.bottom;
        const top = below >= h + 8 || below >= r.top ? r.bottom + 4 : r.top - h - 4;
        pop.style.top = Math.max(8, top) + 'px';
        pop.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
        pop.style.visibility = '';
    }));
    window.addEventListener('scroll', (e) => { if (!e.target.closest?.('.dv-pop')) closeMenus(); }, { passive: true, capture: true });
    window.addEventListener('resize', () => closeMenus());

    document.querySelectorAll('[data-rename]').forEach((b) => b.addEventListener('click', () => {
        closeMenus();
        const d = $('#dlg-rename'), f = d.querySelector('form'), input = f.querySelector('[name=name]');
        f.action = b.dataset.rename; input.value = b.dataset.name; d.showModal();
        const dot = b.dataset.name.lastIndexOf('.'); input.setSelectionRange(0, dot > 0 ? dot : b.dataset.name.length);
    }));
    const openMove = (ids) => {
        closeMenus();
        $('#move-ids').innerHTML = ids.map((id) => `<input type="hidden" name="ids[]" value="${Number(id)}">`).join('');
        $('#dlg-move').showModal();
    };
    document.querySelectorAll('[data-move]').forEach((b) => b.addEventListener('click', () => openMove([b.dataset.move])));
    document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(b.dataset.copy); b.lastChild.textContent = 'কপি হয়েছে ✓'; }
        catch { prompt('লিংকটি কপি করুন:', b.dataset.copy); }
    }));

    // ---- selection ----
    const boxes = [...document.querySelectorAll('input.sel')];
    const bar = $('#bulk');
    const sync = () => {
        const n = boxes.filter((b) => b.checked).length;
        bar.classList.toggle('on', n > 0);
        document.body.classList.toggle('dv-selecting', n > 0);
        $('#sel-count').textContent = n.toLocaleString('bn-BD') + 'টি বাছাই করা';
        if ($('#sel-all')) $('#sel-all').checked = n > 0 && n === boxes.length;
    };
    boxes.forEach((b) => b.addEventListener('change', sync));
    $('#sel-all')?.addEventListener('change', (e) => { boxes.forEach((b) => b.checked = e.target.checked); sync(); });
    $('#sel-clear').addEventListener('click', () => { boxes.forEach((b) => b.checked = false); sync(); });
    $('[data-move-selected]')?.addEventListener('click', () => openMove(boxes.filter((b) => b.checked).map((b) => b.value)));

    if (!CAN_UPLOAD) return;

    // ---- uploads: each file goes in 5 MB pieces, one after another ----
    const queue = [];
    let running = false, failed = 0, done = 0;
    const list = $('#up-list');
    const fmt = (b) => b >= 1073741824 ? (b / 1073741824).toFixed(2) + ' GB' : b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    const uid = () => (crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2));

    function add(file, relative) {
        const li = document.createElement('li');
        li.innerHTML = '<div class="row"><b></b><span class="mute"></span></div><progress max="100" value="0"></progress>';
        li.querySelector('b').textContent = (relative ? relative + '/' : '') + file.name;
        li.querySelector('span').textContent = fmt(file.size);
        list.appendChild(li);
        queue.push({ file, relative, li });
        $('#up').classList.add('on');
        $('#up-close').hidden = true;
        $('#up-title').textContent = 'আপলোড হচ্ছে…';
        if (!running) next();
    }

    function sendPiece(form, onProgress) {
        return new Promise((resolve) => {
            const x = new XMLHttpRequest();
            x.open('POST', UPLOAD_URL);
            x.setRequestHeader('X-CSRF-TOKEN', CSRF);
            x.setRequestHeader('Accept', 'application/json');
            x.upload.onprogress = (e) => e.lengthComputable && onProgress(e.loaded);
            x.onload = () => { let j = {}; try { j = JSON.parse(x.responseText); } catch {} resolve({ status: x.status, json: j }); };
            x.onerror = x.ontimeout = () => resolve({ status: 0, json: {} });
            x.send(form);
        });
    }

    async function uploadOne({ file, relative, li }) {
        const bar = li.querySelector('progress'), note = li.querySelector('span');
        const id = uid();
        let offset = 0, tries = 0;
        do {
            const piece = file.slice(offset, offset + CHUNK);
            const form = new FormData();
            form.append('upload_id', id); form.append('name', file.name); form.append('size', file.size);
            form.append('offset', offset); form.append('relative', relative || '');
            if (FOLDER) form.append('folder', FOLDER);
            form.append('chunk', piece, 'piece');
            const r = await sendPiece(form, (loaded) => { bar.value = file.size ? (offset + loaded) / file.size * 100 : 100; });
            if (r.json.done) { bar.value = 100; note.textContent = '✓ ' + fmt(file.size); return true; }
            if (r.status === 200 && typeof r.json.offset === 'number') { offset = r.json.offset; tries = 0; continue; }
            // Server says "carry on from here": follow it, but give up if it keeps saying so instead of looping forever.
            if (r.status === 409 && typeof r.json.offset === 'number' && ++tries <= 4) { offset = r.json.offset; continue; }
            if (r.status === 422 || r.status === 413 || r.status === 419 || ++tries > 4) {
                note.className = 'bad';
                note.textContent = r.json.message || (r.status === 419 ? 'সেশন শেষ — পাতা রিফ্রেশ করুন' : 'আপলোড হয়নি');
                return false;
            }
            note.textContent = 'আবার চেষ্টা…';
            await new Promise((ok) => setTimeout(ok, 2000 * tries)); // the line dropped: wait and resend this piece
        } while (true);
    }

    async function next() {
        running = true;
        while (queue.length) {
            (await uploadOne(queue.shift())) ? done++ : failed++;
        }
        running = false;
        $('#up-title').textContent = failed ? `${done}টি হয়েছে, ${failed}টি হয়নি` : `${done}টি ফাইল আপলোড হয়েছে`;
        $('#up-close').hidden = false;
        if (!failed) setTimeout(() => location.reload(), 1200);
    }
    $('#up-close').addEventListener('click', () => location.reload());
    window.addEventListener('beforeunload', (e) => { if (running) { e.preventDefault(); e.returnValue = ''; } });

    $('#pick-files').addEventListener('change', (e) => { [...e.target.files].forEach((f) => add(f, '')); e.target.value = ''; });
    $('#pick-folder').addEventListener('change', (e) => {
        [...e.target.files].forEach((f) => add(f, f.webkitRelativePath.split('/').slice(0, -1).join('/')));
        e.target.value = '';
    });

    // ---- drag and drop (files and whole folders) ----
    const drop = $('#drop');
    let depth = 0;
    const hasFiles = (e) => [...(e.dataTransfer?.types || [])].includes('Files');
    window.addEventListener('dragenter', (e) => { if (hasFiles(e)) { depth++; drop.classList.add('on'); } });
    window.addEventListener('dragleave', (e) => { if (hasFiles(e) && --depth <= 0) { depth = 0; drop.classList.remove('on'); } });
    window.addEventListener('dragover', (e) => { if (hasFiles(e)) e.preventDefault(); });
    window.addEventListener('drop', async (e) => {
        if (!hasFiles(e)) return;
        e.preventDefault(); depth = 0; drop.classList.remove('on');
        const entries = [...e.dataTransfer.items].map((i) => i.webkitGetAsEntry && i.webkitGetAsEntry()).filter(Boolean);
        if (!entries.length) { [...e.dataTransfer.files].forEach((f) => add(f, '')); return; }
        const walk = async (entry, dir) => {
            if (entry.isFile) {
                await new Promise((ok) => entry.file((f) => { add(f, dir); ok(); }, ok));
            } else if (entry.isDirectory) {
                const reader = entry.createReader(), path = dir ? dir + '/' + entry.name : entry.name;
                let batch;
                do {
                    batch = await new Promise((ok) => reader.readEntries(ok, () => ok([])));
                    for (const child of batch) await walk(child, path);
                } while (batch.length);
            }
        };
        for (const entry of entries) await walk(entry, '');
    });
})();
</script>
@endpush
