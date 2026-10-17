{{-- The ⋮ menu of one file/folder (list and grid). Needs $it and $trash. --}}
<details class="dv-menu dv-rowmenu">
    <summary aria-label="{{ $it->name }} — আরও">@include('partials.icon', ['n' => 'more', 'size' => 20])</summary>
    <div class="dv-pop">
        @if ($trash)
            <form method="POST" action="{{ route('admin.drive.restore') }}">@csrf<input type="hidden" name="ids[]" value="{{ $it->id }}"><button>@include('partials.icon', ['n' => 'restore', 'size' => 18])ফিরিয়ে আনুন</button></form>
            <form method="POST" action="{{ route('admin.drive.destroy') }}" onsubmit="return confirm('চিরতরে মুছে যাবে। নিশ্চিত?')">@csrf @method('DELETE')<input type="hidden" name="ids[]" value="{{ $it->id }}"><button class="dng">@include('partials.icon', ['n' => 'trash', 'size' => 18])চিরতরে মুছুন</button></form>
        @else
            @if ($it->canOpen())<a href="{{ route('admin.drive.open', $it) }}" target="_blank" @if ($it->viewer()) data-view="{{ $it->viewer() }}" @endif>@include('partials.icon', ['n' => 'monitor', 'size' => 18])খুলে দেখুন</a>@endif
            <a href="{{ route('admin.drive.download', $it) }}">@include('partials.icon', ['n' => 'download', 'size' => 18]){{ $it->is_folder ? 'ডাউনলোড (ZIP)' : 'ডাউনলোড' }}</a>
            @if (($q !== '' || $view) && $it->parent_id)
                <a href="{{ route('admin.drive.index', ['folder' => $it->parent_id]) }}">@include('partials.icon', ['n' => 'folder', 'size' => 18])যে ফোল্ডারে আছে</a>
            @endif
            <button type="button" data-rename="{{ route('admin.drive.update', $it) }}" data-name="{{ $it->name }}">@include('partials.icon', ['n' => 'edit', 'size' => 18])নাম বদলান</button>
            <button type="button" data-move="{{ $it->id }}">@include('partials.icon', ['n' => 'move', 'size' => 18])সরান</button>
            <form method="POST" action="{{ route('admin.drive.star', $it) }}">@csrf<button>@include('partials.icon', ['n' => 'star', 'size' => 18]){{ $it->starred ? 'তারকা সরান' : 'তারকা দিন' }}</button></form>
            @unless ($it->is_folder)
                @if ($it->share_token)
                    <button type="button" data-copy="{{ route('drive.shared', $it->share_token) }}">@include('partials.icon', ['n' => 'link', 'size' => 18])শেয়ার লিংক কপি</button>
                    <form method="POST" action="{{ route('admin.drive.share', $it) }}">@csrf<input type="hidden" name="off" value="1"><button>@include('partials.icon', ['n' => 'link', 'size' => 18])শেয়ার বন্ধ করুন</button></form>
                @else
                    <form method="POST" action="{{ route('admin.drive.share', $it) }}">@csrf<button>@include('partials.icon', ['n' => 'link', 'size' => 18])শেয়ার লিংক তৈরি</button></form>
                @endif
            @endunless
            <form method="POST" action="{{ route('admin.drive.trash') }}">@csrf<input type="hidden" name="ids[]" value="{{ $it->id }}"><button class="dng">@include('partials.icon', ['n' => 'trash', 'size' => 18])ট্র্যাশে ফেলুন</button></form>
        @endif
    </div>
</details>
