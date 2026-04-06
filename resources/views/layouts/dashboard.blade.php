<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }}</title>

    @vite(['resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-body-tertiary">
<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <div class="sidebar-brand">
            <span class="fw-semibold">Redis Dashboard</span>
        </div>
    </div>
    <ul class="sidebar-nav">
        <li class="nav-title">Monitoring</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard.queue') ? 'active' : '' }}"
               href="{{ route('dashboard.queue') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16">
                    <path fill="currentColor" d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zm64 64v64H448V160H64zm0 128v128H448V288H64z"/>
                </svg>
                Queue Summary
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard.users') ? 'active' : '' }}"
               href="{{ route('dashboard.users') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="16" height="16">
                    <path fill="currentColor" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/>
                </svg>
                Users Summary
            </a>
        </li>
    </ul>
</div>

<div class="wrapper d-flex flex-column min-vh-100">
    <header class="header header-sticky d-print-none border-bottom bg-white px-4">
        <div class="d-flex align-items-center gap-3 py-2">
            <button class="header-toggler d-lg-none p-0 border-0 bg-transparent"
                    type="button"
                    onclick="document.getElementById('sidebar').classList.toggle('show')"
                    aria-label="Toggle navigation">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512">
                    <path fill="currentColor" d="M0 96C0 78.3 14.3 64 32 64H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"/>
                </svg>
            </button>
            <span class="fw-semibold">{{ $title ?? 'Dashboard' }}</span>
        </div>
    </header>

    <div class="body flex-grow-1 px-3 py-4">
        <div class="container-lg">
            {{ $slot }}
        </div>
    </div>

    <footer class="footer border-top px-4 py-2">
        <small class="text-body-secondary">Redis Livewire Dashboard</small>
    </footer>
</div>

@livewireScripts
</body>
</html>
