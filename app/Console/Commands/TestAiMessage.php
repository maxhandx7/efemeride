<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\MessageComposer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Diagnostico de la IA. Dice exactamente en que paso se rompe,
 * en vez de dejarte adivinando por que salio la plantilla de siempre.
 */
class TestAiMessage extends Command
{
    protected $signature = 'reminders:ai-test {event? : ID de un evento para redactar de verdad}';

    protected $description = 'Revisa la configuracion de IA y prueba la conexion';

    public function handle(MessageComposer $composer): int
    {
        $cfg = config('reminders.ai');

        // Si faltan llaves, es que config/reminders.php quedo en la version vieja.
        foreach (['provider', 'base_url', 'thinking'] as $key) {
            if (! array_key_exists($key, $cfg)) {
                $this->error("A config/reminders.php le falta la clave 'ai.{$key}'.");
                $this->line('  Reemplaza config/reminders.php y app/Services/MessageComposer.php');
                $this->line('  por las versiones nuevas, y corre php artisan config:clear.');

                return self::FAILURE;
            }
        }

        $this->line('');
        $this->line('  <fg=gray>Proveedor</> '.$cfg['provider']);
        $this->line('  <fg=gray>Modelo   </> '.$cfg['model']);
        $this->line('  <fg=gray>URL      </> '.($cfg['base_url'] ?: 'la de Anthropic'));
        $this->line('  <fg=gray>API key  </> '.($cfg['api_key']
            ? substr($cfg['api_key'], 0, 6).'…'.substr($cfg['api_key'], -4)
            : '<fg=red>vacia</>'));
        $this->line('  <fg=gray>Activada </> '.($cfg['enabled'] ? '<fg=green>si</>' : '<fg=red>no</>'));
        $this->line('');

        if (! $cfg['enabled']) {
            $this->error('REMINDERS_AI_ENABLED esta en false. Ponlo en true y corre php artisan config:clear.');

            return self::FAILURE;
        }

        if (! $cfg['api_key']) {
            $this->error('No hay API key. La variable que se lee es REMINDERS_AI_KEY.');

            return self::FAILURE;
        }

        $this->line('Llamando al modelo...');

        $response = $cfg['provider'] === 'anthropic'
            ? Http::timeout(30)->withHeaders([
                'x-api-key' => $cfg['api_key'],
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $cfg['model'],
                'max_tokens' => 50,
                'messages' => [['role' => 'user', 'content' => 'Responde solo: listo']],
            ])
            : Http::timeout(30)->withToken($cfg['api_key'])
                ->post(rtrim($cfg['base_url'], '/').'/chat/completions', [
                    'model' => $cfg['model'],
                    'max_tokens' => 50,
                    'messages' => [['role' => 'user', 'content' => 'Responde solo: listo']],
                ]);

        if ($response->failed()) {
            $this->error("El proveedor respondio {$response->status()}:");
            $this->line('  '.mb_substr($response->body(), 0, 400));
            $this->line('');
            $this->line('  <fg=yellow>401</> key mala o mal copiada · <fg=yellow>402</> sin saldo · <fg=yellow>404</> modelo o URL equivocada');

            return self::FAILURE;
        }

        $this->info('Conexion correcta.');

        if ($id = $this->argument('event')) {
            $event = Event::findOrFail($id);
            $this->line('');
            $this->line('Mensaje que redactaria para <fg=cyan>'.$event->name.'</>:');
            $this->line('');
            $this->line('  '.str_replace("\n", "\n  ", $composer->forEvent(
                $event,
                $event->daysUntilNext(),
                $event->nextOccurrence()
            )));
            $this->line('');
            $this->comment('Si esto se parece a la plantilla, revisa storage/logs/laravel.log y que el evento tenga marcada la casilla de IA.');
        }

        return self::SUCCESS;
    }
}
