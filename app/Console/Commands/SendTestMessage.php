<?php

namespace App\Console\Commands;

use App\Services\WahaService;
use Illuminate\Console\Command;

/**
 * Manda un mensaje aqui y ahora, sin cola ni recordatorios de por medio.
 * Sirve para saber si el problema es WAHA o es la cola.
 */
class SendTestMessage extends Command
{
    protected $signature = 'waha:send {chat? : Destino, por defecto el tuyo} {--text= : Que decir}';

    protected $description = 'Envia un mensaje de prueba directo por WAHA';

    public function handle(WahaService $waha): int
    {
        $chat = $this->argument('chat') ?: config('waha.default_chat_id');

        if (! $chat) {
            $this->error('No hay destino. Pasalo como argumento o pon WAHA_DEFAULT_CHAT_ID.');

            return self::FAILURE;
        }

        $text = $this->option('text') ?: 'Prueba directa desde Efemeride · '.now(config('reminders.timezone'))->format('H:i');

        $this->line('  <fg=gray>WAHA</>    '.config('waha.base_url'));
        $this->line('  <fg=gray>Sesion</>  '.config('waha.session'));
        $this->line('  <fg=gray>Destino</> '.$chat);
        $this->line('');

        try {
            $id = $waha->sendText($chat, $text);
            $this->info('Enviado. '.($id ? "ID: {$id}" : 'WAHA no devolvio ID, pero acepto el mensaje.'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('No se pudo enviar:');
            $this->line('  '.$e->getMessage());
            $this->line('');
            $this->line('  <fg=yellow>Connection refused</> el contenedor no alcanza a WAHA: revisa WAHA_BASE_URL');
            $this->line('  <fg=yellow>401 / 403</>          falta o esta mal WAHA_API_KEY');
            $this->line('  <fg=yellow>422</>                el chatId no existe o la sesion no esta lista');

            return self::FAILURE;
        }
    }
}
