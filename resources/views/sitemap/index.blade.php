<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0">
@foreach($urls as $item)
    <url>
        <loc>{{ htmlspecialchars($item['url'], ENT_XML1, 'UTF-8') }}</loc>
        <lastmod>{{ $item['lastmod'] }}</lastmod>
        <changefreq>{{ $item['changefreq'] }}</changefreq>
        <priority>{{ $item['priority'] }}</priority>
    </url>
@endforeach
</urlset>
