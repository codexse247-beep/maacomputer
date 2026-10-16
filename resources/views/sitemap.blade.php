{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $name => $priority)
    @php $url = route($name); @endphp
    <url>
        <loc>{{ $url }}</loc>
        <xhtml:link rel="alternate" hreflang="bn" href="{{ $url }}?lang=bn"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ $url }}?lang=en"/>
        <lastmod>{{ $updated->toDateString() }}</lastmod>
        <priority>{{ $priority }}</priority>
    </url>
@endforeach
</urlset>
