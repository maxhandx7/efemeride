<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Http\Requests\EventRequest;
use App\Jobs\SendWhatsappMessage;
use App\Models\Event;
use App\Models\ReminderLog;
use App\Services\MessageComposer;
use App\Services\WahaService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request, WahaService $waha)
    {
        $events = Event::with('rules')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->get()
            ->sortBy(fn (Event $e) => $e->daysUntilNext())
            ->values();

        return view('events.index', [
            'events' => $events,
            'connected' => $waha->isReady(),
            'lastSends' => ReminderLog::with('event')->latest('id')->limit(8)->get(),
        ]);
    }

    public function create()
    {
        return view('events.form', [
            'event' => new Event([
                'type' => EventType::Birthday,
                'send_at' => config('reminders.default_send_at').':00',
                'is_active' => true,
            ]),
            'selectedDays' => config('reminders.default_days_before'),
        ]);
    }

    public function store(EventRequest $request)
    {
        $event = Event::create($request->validated());
        $this->syncRules($event, $request->input('days_before', config('reminders.default_days_before')));

        return redirect()->route('events.index')
            ->with('status', "Listo, {$event->name} queda en el radar.");
    }

    public function edit(Event $event)
    {
        return view('events.form', [
            'event' => $event,
            'selectedDays' => $event->rules->pluck('days_before')->all(),
        ]);
    }

    public function update(EventRequest $request, Event $event)
    {
        $event->update($request->validated());
        $this->syncRules($event, $request->input('days_before', []));

        return redirect()->route('events.index')
            ->with('status', "{$event->name} actualizado.");
    }

    public function destroy(Event $event)
    {
        $name = $event->name;
        $event->delete();

        return redirect()->route('events.index')->with('status', "{$name} salio de la lista.");
    }

    /**
     * Botón "probar": manda el mensaje ya mismo, sin esperar al calendario.
     * No pasa por reminder_logs a propósito: una prueba no debe gastar el aviso real
     * ni chocar con el candado anti-duplicados si le das dos veces.
     */
    public function test(Event $event, MessageComposer $composer)
    {
        $chatId = $event->destinationChatId();

        if (! $chatId) {
            return back()->with('status', 'Este evento no tiene destino y no hay número por defecto configurado.');
        }

        $days = $event->daysUntilNext();

        $message = '🧪 Prueba · '.$composer->forEvent($event, $days, $event->nextOccurrence());

        SendWhatsappMessage::dispatch($chatId, $message);

        return back()->with('status', 'Mensaje de prueba en camino.');
    }

    protected function syncRules(Event $event, array $days): void
    {
        $days = collect($days)->map(fn ($d) => (int) $d)->unique()->sort()->values();

        $event->rules()->whereNotIn('days_before', $days)->delete();

        foreach ($days as $d) {
            $event->rules()->firstOrCreate(['days_before' => $d]);
        }
    }
}
