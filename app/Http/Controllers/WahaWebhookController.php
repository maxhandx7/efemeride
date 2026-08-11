<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\MessageComposer;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Contestar por WhatsApp: escribes "hoy" o "que viene" y la app responde.
 * Configura esta URL en el webhook de tu WAHA (evento: message).
 */
class WahaWebhookController extends Controller
{
    public function __invoke(Request $request, WahaService $waha, MessageComposer $composer)
    {
        if (! $this->authorized($request)) {
            return response()->json(['ok' => false], 401);
        }

        if ($request->input('event') !== 'message') {
            return response()->json(['ok' => true]);
        }

        $payload = $request->input('payload', []);
        $from = $payload['from'] ?? null;
        $body = trim((string) ($payload['body'] ?? ''));

        if (! $from || ($payload['fromMe'] ?? false)) {
            return response()->json(['ok' => true]);
        }

        if (! $this->isAdmin($from)) {
            Log::info('Mensaje ignorado de numero no autorizado', ['from' => $from]);

            return response()->json(['ok' => true]);
        }

        $reply = $this->handleCommand($body, $composer);

        if ($reply) {
            $waha->sendSeen($from, $payload['id'] ?? null);
            $waha->sendText($from, $reply);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleCommand(string $body, MessageComposer $composer): ?string
    {
        $cmd = Str::of($body)->lower()->ascii()->trim();

        return match (true) {
            $cmd->startsWith(['hoy', 'que hay hoy']) => $this->today(),
            $cmd->startsWith(['proximos', 'que viene', 'agenda']) => $composer->digest(
                Event::active()->get()
                    ->filter(fn (Event $e) => $e->daysUntilNext() <= 30)
                    ->sortBy(fn (Event $e) => $e->daysUntilNext()),
                30
            ),
            $cmd->startsWith('agregar ') => $this->quickAdd($body),
            $cmd->startsWith(['ayuda', 'menu', 'help']) => $this->help(),
            default => null,
        };
    }

    protected function today(): string
    {
        $events = Event::active()->get()->filter(fn (Event $e) => $e->daysUntilNext() === 0);

        if ($events->isEmpty()) {
            return 'Hoy no cumple nadie. Dia libre de compromisos sociales. 🎉';
        }

        return "*Hoy:*\n".$events->map(function (Event $e) {
            $age = $e->ageAtNextOccurrence();

            return "{$e->type->emoji()} {$e->name}".($age !== null ? " ({$age} anios)" : '');
        })->implode("\n");
    }

    /** "agregar Marcela 12/05" o "agregar Marcela 1990-05-12" */
    protected function quickAdd(string $body): string
    {
        $rest = trim(Str::after($body, ' '));

        if (! preg_match('#^(.+?)\s+(\d{4}-\d{1,2}-\d{1,2}|\d{1,2}[/-]\d{1,2})$#u', $rest, $m)) {
            return 'Formato: agregar Nombre 12/05  (o agregar Nombre 1990-05-12)';
        }

        [$name, $date] = [trim($m[1]), $m[2]];

        if (str_contains($date, '-') && strlen($date) > 5) {
            [$year, $month, $day] = array_map('intval', explode('-', $date));
        } else {
            [$day, $month] = array_map('intval', preg_split('#[/-]#', $date));
            $year = null;
        }

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return 'Esa fecha no me cuadra. Revisala.';
        }

        $event = Event::create([
            'name' => $name,
            'type' => 'birthday',
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'send_at' => config('reminders.default_send_at').':00',
        ]);

        foreach (config('reminders.default_days_before') as $d) {
            $event->rules()->create(['days_before' => $d]);
        }

        return "Anotado: {$event->name}, {$event->formatted_date}. Faltan {$event->daysUntilNext()} dias.";
    }

    protected function help(): string
    {
        return "*Que se hacer:*\n".
            "• *hoy* — quien cumple hoy\n".
            "• *proximos* — lo que viene en 30 dias\n".
            "• *agregar Nombre 12/05* — anotar una fecha nueva\n".
            '• *ayuda* — esto mismo';
    }

    protected function authorized(Request $request): bool
    {
        $secret = config('waha.webhook_secret');

        if (! $secret) {
            return true; // sin secreto configurado, no filtramos (pon uno en produccion)
        }

        return hash_equals($secret, (string) ($request->header('X-Webhook-Token') ?: $request->query('token')));
    }

    protected function isAdmin(string $from): bool
    {
        $admins = config('waha.admin_numbers');

        if (empty($admins)) {
            return true;
        }

        $digits = preg_replace('/\D+/', '', $from);

        foreach ($admins as $admin) {
            if (str_contains($digits, preg_replace('/\D+/', '', $admin))) {
                return true;
            }
        }

        return false;
    }
}
