{{-- Plain-text copy of the same email (for apps that don't show HTML, and better spam scores). --}}
{!! setting('name') !!}

{!! $title !!}

{!! trim(str_replace("\r\n", "\n", $body)) !!}
@if (! empty($button))

{!! $button[0] !!}: {!! $button[1] !!}
@endif

--
{!! setting('name') !!} · {!! route('home') !!}
