<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WahaService
{
    public function __construct(
        protected ?string $baseUrl = null,
        protected ?string $session = null,
    ) {
        $this->baseUrl = $baseUrl ?: config('waha.base_url');
        $this->session = $session ?: config('waha.session');
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(config('waha.timeout'))
            ->acceptJson()
            ->when(config('waha.api_key'), fn ($http) => $http->withHeaders([
                'X-Api-Key' => config('waha.api_key'),
            ]));
    }

    /**
     * Convierte cualquier cosa parecida a un numero en un chatId valido.
     * "3001112233" -> "573001112233@c.us"
     */
    public function normalizeChatId(string $raw): string
    {
        $raw = trim($raw);

        if (str_contains($raw, '@')) {
            return $raw; // ya viene con sufijo (@c.us o @g.us)
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            throw new RuntimeException("No pude entender el destino: {$raw}");
        }

        $cc = config('waha.country_code');

        if (strlen($digits) === 10 && ! str_starts_with($digits, $cc)) {
            $digits = $cc.$digits;
        }

        return $digits.'@c.us';
    }

    public function sendText(string $chatId, string $text): ?string
    {
        $chatId = $this->normalizeChatId($chatId);

        if (config('waha.simulate_typing')) {
            $this->typing($chatId, true);
            usleep(min(3_000_000, max(600_000, strlen($text) * 25_000)));
            $this->typing($chatId, false);
        }

        $response = $this->client()->post('/api/sendText', [
            'session' => $this->session,
            'chatId' => $chatId,
            'text' => $text,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "WAHA respondio {$response->status()}: ".mb_substr($response->body(), 0, 500)
            );
        }

        return $this->extractMessageId($response->json());
    }

    public function sendSeen(string $chatId, ?string $messageId = null): void
    {
        rescue(fn () => $this->client()->post('/api/sendSeen', array_filter([
            'session' => $this->session,
            'chatId' => $this->normalizeChatId($chatId),
            'messageId' => $messageId,
        ])), report: false);
    }

    public function typing(string $chatId, bool $on): void
    {
        rescue(fn () => $this->client()->post($on ? '/api/startTyping' : '/api/stopTyping', [
            'session' => $this->session,
            'chatId' => $this->normalizeChatId($chatId),
        ]), report: false);
    }

    /** ¿La sesion esta viva o el telefono se desconecto otra vez? */
    public function sessionStatus(): array
    {
        $response = $this->client()->get("/api/sessions/{$this->session}");

        if ($response->failed()) {
            return ['status' => 'UNREACHABLE', 'error' => $response->status()];
        }

        return $response->json() ?? [];
    }

    public function isReady(): bool
    {
        return rescue(
            fn () => ($this->sessionStatus()['status'] ?? null) === 'WORKING',
            false,
            report: false
        );
    }

    protected function extractMessageId(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $id = $payload['id'] ?? null;

        if (is_string($id)) {
            return $id;
        }

        if (is_array($id)) {
            return $id['_serialized'] ?? $id['id'] ?? null;
        }

        Log::debug('WAHA devolvio un id con forma rara', ['payload' => $payload]);

        return null;
    }
}
