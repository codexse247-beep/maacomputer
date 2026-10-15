@php
    $id = $id ?? str_replace(['[', ']'], '', $name);
    $key = str_replace('[]', '', $name);
@endphp
<div class="ff">
    <span class="lbl">{{ $label }}</span>
    <input id="{{ $id }}" class="vh" type="file" name="{{ $name }}" accept="{{ $accept }}" @if (! empty($multiple)) multiple @endif>
    <label class="up" for="{{ $id }}" data-text="{{ $btn }}">{{ $btn }}</label>
    @error($key)<span class="errm">{{ $message }}</span>@enderror
    @error($key.'.*')<span class="errm">{{ $message }}</span>@enderror
</div>
