<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="University Management System - Manage students, courses, instructors, classrooms, and departments with advanced reporting features.">
    <meta name="keywords" content="University, Management, Students, Courses, Instructors, Classrooms, Departments, Reporting">
    <meta name="author" content="{{ config('app.name', 'Laravel') }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#321fdb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="University Management System">
    <meta property="og:description" content="Manage students, courses, instructors, classrooms, and departments with advanced reporting features.">
    <meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">

    <!-- Canonical -->
    <link rel="canonical" href="{{ request()->url() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='50' font-size='90' fill='%23321fdb' text-anchor='middle' dominant-baseline='middle'>U</text></svg>">

    <!-- Fonts - Trending Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <title id="page-title">University Management System</title>
    
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/university/main.jsx'])
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div id="university-app"></div>
</body>
</html>
