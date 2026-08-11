@extends('layouts.app')
@section('title', $event->exists ? 'Editar fecha' : 'Nueva fecha')

@section('content')
    <form method="POST" action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}" class="space-y-8">
        @csrf
        @if ($event->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-lg border border-berry/30 bg-berry/10 px-4 py-3 text-sm text-berry">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="font-mono text-xs uppercase tracking-widest text-pine">Nombre</span>
                <input name="name" value="{{ old('name', $event->name) }}" required
                       class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 focus:border-pine focus:outline-none">
            </label>

            <label class="block">
                <span class="font-mono text-xs uppercase tracking-widest text-pine">Que se celebra</span>
                <select name="type" class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 focus:border-pine focus:outline-none">
                    @foreach (\App\Enums\EventType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $event->type?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="font-mono text-xs uppercase tracking-widest text-pine">Hora del aviso</span>
                <input type="time" name="send_at" value="{{ old('send_at', \Illuminate\Support\Str::substr($event->send_at ?? '08:00', 0, 5)) }}"
                       class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 focus:border-pine focus:outline-none">
            </label>

            <div class="grid grid-cols-3 gap-3 sm:col-span-2">
                <label class="block">
                    <span class="font-mono text-xs uppercase tracking-widest text-pine">Dia</span>
                    <input type="number" name="day" min="1" max="31" value="{{ old('day', $event->day) }}" required
                           class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 font-mono focus:border-pine focus:outline-none">
                </label>
                <label class="block">
                    <span class="font-mono text-xs uppercase tracking-widest text-pine">Mes</span>
                    <input type="number" name="month" min="1" max="12" value="{{ old('month', $event->month) }}" required
                           class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 font-mono focus:border-pine focus:outline-none">
                </label>
                <label class="block">
                    <span class="font-mono text-xs uppercase tracking-widest text-pine">Anio</span>
                    <input type="number" name="year" min="1900" max="{{ date('Y') }}" value="{{ old('year', $event->year) }}" placeholder="opcional"
                           class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 font-mono placeholder:text-mist focus:border-pine focus:outline-none">
                </label>
                <p class="col-span-3 text-xs text-pine/80">Si no sabes el anio, dejalo vacio: el aviso llega igual, solo que sin la edad.</p>
            </div>

            <fieldset class="sm:col-span-2">
                <legend class="font-mono text-xs uppercase tracking-widest text-pine">Cuando avisar</legend>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ([0 => 'El mismo dia', 1 => 'Un dia antes', 3 => '3 dias antes', 7 => 'Una semana', 15 => '15 dias', 30 => 'Un mes'] as $days => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="days_before[]" value="{{ $days }}" class="peer sr-only"
                                   @checked(in_array($days, old('days_before', $selectedDays)))>
                            <span class="block rounded-full border border-mist px-3.5 py-1.5 text-sm transition peer-checked:border-ink peer-checked:bg-ink peer-checked:text-paper peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-pine">
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="block sm:col-span-2">
                <span class="font-mono text-xs uppercase tracking-widest text-pine">Enviar a</span>
                <input name="chat_id" value="{{ old('chat_id', $event->chat_id) }}" placeholder="Tu numero por defecto"
                       class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 font-mono text-sm placeholder:text-mist focus:border-pine focus:outline-none">
                <span class="mt-1 block text-xs text-pine/80">Numero o grupo. Vacio = te llega a ti.</span>
            </label>

            <label class="block sm:col-span-2">
                <span class="font-mono text-xs uppercase tracking-widest text-pine">Mensaje propio</span>
                <textarea name="template" rows="3" placeholder="Deja vacio para usar el mensaje estandar"
                          class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 text-sm placeholder:text-mist focus:border-pine focus:outline-none">{{ old('template', $event->template) }}</textarea>
                <span class="mt-1 block font-mono text-xs text-pine/80">Etiquetas: {nombre} {dias} {fecha} {edad} {edad_frase}</span>
            </label>

            <label class="block sm:col-span-2">
                <span class="font-mono text-xs uppercase tracking-widest text-pine">Notas</span>
                <textarea name="notes" rows="2" placeholder="Le gusta el cafe, odia las sorpresas..."
                          class="mt-2 w-full rounded-lg border border-mist bg-white px-4 py-2.5 text-sm placeholder:text-mist focus:border-pine focus:outline-none">{{ old('notes', $event->notes) }}</textarea>
            </label>

            <div class="space-y-3 sm:col-span-2">
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="use_ai" value="1" @checked(old('use_ai', $event->use_ai)) class="h-4 w-4 rounded border-mist text-pine focus:ring-pine">
                    Que la IA redacte el mensaje usando las notas
                </label>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $event->is_active ?? true)) class="h-4 w-4 rounded border-mist text-pine focus:ring-pine">
                    Activo
                </label>
            </div>
        </div>

        <div class="flex items-center gap-4 border-t border-mist pt-6">
            <button class="rounded-full bg-ink px-6 py-2.5 text-sm font-medium text-paper transition hover:bg-pine">
                {{ $event->exists ? 'Guardar cambios' : 'Anotar fecha' }}
            </button>
            <a href="{{ route('events.index') }}" class="text-sm text-pine underline-offset-4 hover:underline">Cancelar</a>

            @if ($event->exists)
                <span class="ml-auto"></span>
            @endif
        </div>
    </form>

    @if ($event->exists)
        <form method="POST" action="{{ route('events.destroy', $event) }}" class="mt-6"
              onsubmit="return confirm('¿Borrar {{ $event->name }} de la lista?')">
            @csrf @method('DELETE')
            <button class="text-sm text-berry underline-offset-4 hover:underline">Borrar esta fecha</button>
        </form>
    @endif
@endsection
