@php
    $id = $id ?? str_replace(['[', ']', '.'], ['_', '', '_'], $name);
    $dot = str_replace(['[', ']'], ['.', ''], $name);
    $val = old($dot, $value ?? '');
    if ($val instanceof \Carbon\CarbonInterface) { $val = $val->format('Y-m-d'); }
@endphp
<div class="ff">
    <label for="{{ $id }}">{{ $label }} @if (! empty($req))<span class="req" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" class="in {{ $errors->has($dot) ? 'bad' : '' }}" type="{{ $type ?? 'text' }}" value="{{ $val }}"
        @if (! empty($ph)) placeholder="{{ $ph }}" @endif
        @if (! empty($mode)) inputmode="{{ $mode }}" @endif
        @if (! empty($req)) required @endif
        @if (! empty($readonly)) readonly @endif
        @if ($errors->has($dot)) aria-invalid="true" aria-describedby="{{ $id }}-err" @endif>
    @error($dot)<span class="errm" id="{{ $id }}-err">{{ $message }}</span>@enderror
    @if (! empty($hint))<small>{{ $hint }}</small>@endif
</div>
