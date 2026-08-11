@extends('layouts.app')
@section('title', 'Bienvenido')

@section('content')
    <form method="GET" class="mb-8">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre"
               class="w-full rounded-lg border border-mist bg-white px-4 py-2.5 text-sm placeholder:text-mist focus:border-pine focus:outline-none">
    </form>

    @forelse ($events as $event)
        @php
            $days = $event->daysUntilNext();
            $age = $event->ageAtNextOccurrence();
            $urgent = $days <= 1;
        @endphp
        <article class="group flex items-start gap-5 border-b border-mist py-5">
            {{-- La cuenta regresiva es lo primero que ves. Es el unico dato que importa. --}}
            <div class="w-16 shrink-0 text-right">
                <div class="font-mono text-3xl font-bold leading-none {{ $urgent ? 'text-berry' : 'text-ink' }}">
                    {{ $days }}
                </div>
                <div class="mt-1 font-mono text-[10px] uppercase tracking-widest text-pine/70">
                    {{ $days === 1 ? 'dia' : 'dias' }}
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="font-display text-xl font-semibold leading-tight">
                    <span aria-hidden="true">{{ $event->type->emoji() }}</span>
                    {{ $event->name }}
                    @unless ($event->is_active)
                        <span class="ml-1 align-middle font-mono text-[10px] uppercase tracking-widest text-mist">pausado</span>
                    @endunless
                </h2>

                <p class="mt-1 text-sm text-pine">
                    {{ $event->nextOccurrence()->locale('es')->translatedFormat('l j \d\e F') }}
                    @if ($age !== null) · cumple {{ $age }} @endif
                    · aviso {{ \Illuminate\Support\Str::of($event->send_at)->substr(0, 5) }}
                </p>

                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    @foreach ($event->rules->sortByDesc('days_before') as $rule)
                        <span class="rounded border border-mist px-1.5 py-0.5 font-mono text-[10px] text-pine">
                            D-{{ $rule->days_before }}
                        </span>
                    @endforeach
                    @if ($event->use_ai)
                        <span class="rounded bg-citrus/25 px-1.5 py-0.5 font-mono text-[10px] text-ink">redactado por IA</span>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3 text-sm">
                <form method="POST" action="{{ route('events.test', $event) }}">
                    @csrf
                    <button class="text-pine underline-offset-4 hover:underline">Probar</button>
                </form>
                <a href="{{ route('events.edit', $event) }}" class="text-pine underline-offset-4 hover:underline">Editar</a>
            </div>
        </article>
    @empty
        <div class="rounded-xl border border-dashed border-mist px-6 py-14 text-center">
            <p class="font-display text-xl">Todavia no hay ninguna fecha aqui.</p>
            <p class="mt-2 text-sm text-pine">Anota la primera y el resto lo hace el servidor.</p>
            <a href="{{ route('events.create') }}" class="mt-5 inline-block rounded-full bg-ink px-5 py-2 text-sm text-paper hover:bg-pine">Anotar fecha</a>
        </div>
    @endforelse

    @if ($lastSends->isNotEmpty())
        <section class="mt-14">
            <h2 class="font-mono text-xs uppercase tracking-widest text-pine">Ultimos envios</h2>
            <ul class="mt-4 space-y-2">
                @foreach ($lastSends as $log)
                    <li class="flex items-center gap-3 text-sm">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $log->status === 'sent' ? 'bg-pine' : ($log->status === 'failed' ? 'bg-berry' : 'bg-citrus') }}"></span>
                        <span class="font-mono text-xs text-pine/70">{{ $log->created_at->format('d/m H:i') }}</span>
                        <span class="truncate">{{ $log->event?->name ?? 'evento borrado' }} · D-{{ $log->days_before }}</span>
                        @if ($log->status === 'failed')
                            <span class="ml-auto truncate font-mono text-xs text-berry" title="{{ $log->error }}">fallo</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
