@if ($paginator->hasPages())
<nav aria-label="পাতা" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
    <span class="mute" style="font-size:15px">{{ bn($paginator->total()) }} জনের/টির মধ্যে {{ bn($paginator->firstItem()) }}–{{ bn($paginator->lastItem()) }} দেখানো হচ্ছে</span>
    <div style="display:flex;flex-wrap:wrap;gap:4px">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pg">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <a class="pg {{ $page == $paginator->currentPage() ? 'on' : '' }}" href="{{ $url }}" @if ($page == $paginator->currentPage()) aria-current="page" @endif>{{ bn($page) }}</a>
                @endforeach
            @endif
        @endforeach
    </div>
</nav>
@endif
