<?php

namespace App\Enums;

enum EventType: string
{
    case Birthday = 'birthday';
    case Anniversary = 'anniversary';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Birthday => 'Cumpleanos',
            self::Anniversary => 'Aniversario',
            self::Custom => 'Fecha especial',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Birthday => '🎂',
            self::Anniversary => '💍',
            self::Custom => '📌',
        };
    }

    /** ¿Tiene sentido contar anios cumplidos para este tipo? */
    public function countsYears(): bool
    {
        return $this !== self::Custom;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
