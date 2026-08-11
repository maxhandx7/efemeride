<?php

namespace Tests\Unit;

use App\Services\WahaService;
use Tests\TestCase;

class ChatIdTest extends TestCase
{
    protected function normalize(string $raw, string $cc = '57', int $len = 10): string
    {
        config(['waha.country_code' => $cc, 'waha.local_length' => $len]);

        return app(WahaService::class)->normalizeChatId($raw);
    }

    public function test_le_pone_el_prefijo_a_un_numero_local(): void
    {
        $this->assertSame('573001112233@c.us', $this->normalize('3001112233'));
        $this->assertSame('573001112233@c.us', $this->normalize('300 111 2233'));
    }

    public function test_no_duplica_el_prefijo_si_ya_viene(): void
    {
        $this->assertSame('573001112233@c.us', $this->normalize('573001112233'));
    }

    public function test_el_signo_mas_manda(): void
    {
        $this->assertSame('34612345678@c.us', $this->normalize('+34 612 345 678'));
    }

    public function test_funciona_con_otro_pais(): void
    {
        $this->assertSame('34612345678@c.us', $this->normalize('612345678', '34', 9));
    }

    public function test_respeta_los_grupos_y_lo_que_ya_trae_sufijo(): void
    {
        $this->assertSame('1234567890-1600000000@g.us', $this->normalize('1234567890-1600000000@g.us'));
        $this->assertSame('573001112233@c.us', $this->normalize('573001112233@c.us'));
    }
}
