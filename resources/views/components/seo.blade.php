<!-- SEO Meta Tags Component -->
<!-- Usage: @include('components.seo', ['title' => 'Page Title', 'description' => 'Page description']) -->

@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'ogImage' => null,
    'canonical' => null,
])

@php
    $appName = config('app.name', 'Laravel');
    $finalTitle = $title ? "{$title} - {$appName}" : "{$appName} - Redis Queue & User Dashboard";
    $finalDescription = $description ?? 'Monitor queue status, track email verifications, and manage user data with advanced Redis integration.';
    $finalKeywords = $keywords ?? 'Laravel, Redis, Queue, Dashboard, Monitoring';
    $ogImageUrl = $ogImage ?? asset('images/og-default.png');
    $currentUrl = $canonical ?? request()->url();
@endphp

<!-- SEO Meta Tags -->
<meta name="description" content="{{ $finalDescription }}">
<meta name="keywords" content="{{ $finalKeywords }}">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

<!-- Open Graph Tags -->
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $finalTitle }}">
<meta property="og:description" content="{{ $finalDescription }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:url" content="{{ $currentUrl }}">
<meta property="og:site_name" content="{{ $appName }}">

<!-- Twitter Card Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $finalTitle }}">
<meta name="twitter:description" content="{{ $finalDescription }}">
<meta name="twitter:image" content="{{ $ogImageUrl }}">

<!-- Canonical URL -->
<link rel="canonical" href="{{ $currentUrl }}">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='50' font-size='90' fill='%231b1b18' text-anchor='middle' dominant-baseline='middle'>R</text></svg>">
<link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 180'><rect fill='%231b1b18' width='180' height='180'/><text y='90' font-size='120' fill='white' text-anchor='middle' dominant-baseline='middle' font-weight='bold'>R</text></svg>">
