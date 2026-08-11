<?php

namespace App\Services;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageComposer
{
    /** Arma el texto del aviso para un evento y una antelacion concreta. */
    public function forEvent(Event $event, int $daysBefore, CarbonImmutable $occurrence): string
    {
        $replacements = $this->replacements($event, $daysBefore, $occurrence);

        if ($event->use_ai && config('reminders.ai.enabled') && config('reminders.ai.api_key')) {
            if ($aiText = $this->askAi($event, $daysBefore, $replacements)) {
                return $aiText;
            }
        }

        $template = $event->template ?: $this->defaultTemplate($event, $daysBefore);

        return trim(strtr($template, $replacements));
    }

    protected function defaultTemplate(Event $event, int $daysBefore): string
    {
        $set = config('reminders.templates.'.$event->type->value, config('reminders.templates.custom'));

        return $daysBefore === 0 ? $set['today'] : $set['before'];
    }

    protected function replacements(Event $event, int $daysBefore, CarbonImmutable $occurrence): array
    {
        $age = $event->ageAtNextOccurrence();

        $ageSentence = match (true) {
            $age === null => '',
            $event->type->value === 'anniversary' => " Cumplen {$age} anios.",
            default => " Cumple {$age}.",
        };

        return [
            '{nombre}' => $event->name,
            '{dias}' => (string) $daysBefore,
            '{fecha}' => $occurrence->locale('es')->translatedFormat('l j \d\e F'),
            '{fecha_corta}' => $occurrence->format('d/m'),
            '{edad}' => $age !== null ? (string) $age : '',
            '{edad_frase}' => $ageSentence,
            '{tipo}' => $event->type->label(),
            '{notas}' => (string) $event->notes,
        ];
    }

    /**
     * Mensaje escrito por IA. Si algo falla, no pasa nada: volvemos a la plantilla.
     */
    protected function askAi(Event $event, int $daysBefore, array $replacements): ?string
    {
        $prompt = $this->prompt($event, $daysBefore, $replacements);

        try {
            $text = config('reminders.ai.provider') === 'anthropic'
                ? $this->callAnthropic($prompt)
                : $this->callOpenAiCompatible($prompt);

            return trim((string) $text) ?: null;
        } catch (\Throwable $e) {
            Log::warning('Fallo la redaccion con IA, uso la plantilla: '.$e->getMessage());

            return null;
        }
    }

    protected function prompt(Event $event, int $daysBefore, array $replacements): string
    {
        $when = $daysBefore === 0 ? 'es hoy' : "es en {$daysBefore} dias";
        $age = $replacements['{edad}'] !== '' ? "Cumple {$replacements['{edad}']} anios." : '';
        $notes = $event->notes ? "Contexto de la persona: {$event->notes}" : '';

        return <<<PROMPT
        Escribe un recordatorio de WhatsApp para mi (soy quien lo recibe, no la persona homenajeada).
        Evento: {$event->type->label()} de {$event->name}, que {$when} ({$replacements['{fecha}']}). {$age}
        {$notes}

        Reglas:
        - Maximo 2 frases, tono {$this->tone()}.
        - Es un aviso para MI, no una felicitacion para esa persona.
        - Puedes sugerir una idea concreta de detalle o mensaje si el contexto lo permite.
        - Responde solo con el texto del mensaje, sin comillas ni explicaciones.
        PROMPT;
    }

    /** DeepSeek, Groq, OpenAI, Ollama... cualquiera que hable el formato de OpenAI. */
    protected function callOpenAiCompatible(string $prompt): ?string
    {
        $body = [
            'model' => config('reminders.ai.model'),
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 300,
            'stream' => false,
        ];

        // Parametro propio de DeepSeek V4. Los demas proveedores lo ignoran.
        if (str_contains(config('reminders.ai.base_url'), 'deepseek')) {
            $body['thinking'] = ['type' => config('reminders.ai.thinking') ? 'enabled' : 'disabled'];
        }

        $response = Http::timeout(30)
            ->withToken(config('reminders.ai.api_key'))
            ->post(config('reminders.ai.base_url').'/chat/completions', $body);

        if ($response->failed()) {
            Log::warning('La IA respondio con error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return null;
        }

        return $response->json('choices.0.message.content');
    }

    protected function callAnthropic(string $prompt): ?string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => config('reminders.ai.api_key'),
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => config('reminders.ai.model'),
                'max_tokens' => 300,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

        if ($response->failed()) {
            Log::warning('La IA respondio con error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return null;
        }

        return collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");
    }

    protected function tone(): string
    {
        return config('reminders.ai.tone');
    }

    /** Resumen semanal: una sola tarjeta con todo lo que viene. */
    public function digest(iterable $events, int $horizon): string
    {
        $lines = collect($events)
            ->map(function (Event $event) {
                $days = $event->daysUntilNext();
                $age = $event->ageAtNextOccurrence();
                $when = match (true) {
                    $days === 0 => 'HOY',
                    $days === 1 => 'manana',
                    default => "en {$days} dias",
                };
                $ageLabel = $age !== null ? " ({$age})" : '';

                return "{$event->type->emoji()} *{$event->name}*{$ageLabel} — {$when}, {$event->nextOccurrence()->format('d/m')}";
            })
            ->implode("\n");

        if ($lines === '') {
            return "🗓️ Semana tranquila: no hay nada en los proximos {$horizon} dias. Disfrutalo.";
        }

        return "🗓️ *Lo que viene en {$horizon} dias*\n\n{$lines}";
    }
}
