<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bienvenido') · Efemeride | Agenda de duvis</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Public+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        paper: '#F2F4F1',
                        ink: '#16201C',
                        pine: '#2F5D50',
                        citrus: '#E2B33C',
                        berry: '#8C3F5D',
                        mist: '#C9D3CB',
                    },
                    fontFamily: {
                        display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
                        sans: ['"Public Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                    },
                },
            },
        }
    </script>
    <style>
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
        .rail { background-image: repeating-linear-gradient(to right, #C9D3CB 0 1px, transparent 1px 8px); }
    </style>
</head>
<body class="h-full bg-paper text-ink font-sans antialiased">
    <div class="mx-auto max-w-4xl px-5 py-10 sm:py-14">
        <header class="mb-10 flex flex-wrap items-end justify-between gap-4 border-b border-mist pb-6">
            <div>
                <a href="{{ route('events.index') }}" class="font-display text-3xl font-bold tracking-tight">Efemeride</a>
                <p class="mt-1 text-sm text-pine">Agenda de duvis</p>
            </div>
            <div class="flex items-center gap-4">
                @isset($connected)
                    <span class="flex items-center gap-2 font-mono text-xs uppercase tracking-widest {{ $connected ? 'text-pine' : 'text-berry' }}">
                        <span class="h-2 w-2 rounded-full {{ $connected ? 'bg-pine' : 'bg-berry' }}"></span>
                        {{ $connected ? 'WhatsApp conectado' : 'WhatsApp caido' }}
                    </span>
                @endisset
                <a href="{{ route('events.create') }}"
                   class="rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper transition hover:bg-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine">
                    Anotar fecha
                </a>
            </div>
        </header>

        @if (session('status'))
            <div class="mb-8 rounded-lg border border-pine/30 bg-pine/10 px-4 py-3 text-sm text-pine">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')

        <footer class="mt-16 border-t border-mist pt-6 font-mono text-xs text-pine/70">
            Hora local {{ config('reminders.timezone') }} · {{ now(config('reminders.timezone'))->format('d/m/Y H:i') }}
        </footer>
    </div>
</body>
</html>
