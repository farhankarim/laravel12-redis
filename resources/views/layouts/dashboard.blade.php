<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <h1 class="text-lg font-semibold">Redis Livewire Dashboard</h1>

            <nav class="flex items-center gap-2 text-sm">
                <a
                    href="{{ route('dashboard.queue') }}"
                    class="rounded border px-3 py-1.5 {{ request()->routeIs('dashboard.queue') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}"
                >
                    Queue Summary
                </a>
                <a
                    href="{{ route('dashboard.users') }}"
                    class="rounded border px-3 py-1.5 {{ request()->routeIs('dashboard.users') ? 'bg-gray-900 text-white' : 'bg-white text-gray-700' }}"
                >
                    Users Summary
                </a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
