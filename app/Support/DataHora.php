<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Formats stored dates (application timezone, UTC) in the operator's timezone.
 * Only presentation changes; stored values are never converted.
 */
final class DataHora
{
    public static function exibir(DateTimeInterface|string|null $valor, string $formato = 'd/m/Y H:i'): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return Carbon::parse($valor, config('app.timezone'))
            ->setTimezone(self::fuso())
            ->format($formato);
    }

    public static function fuso(): string
    {
        return (string) config('app.display_timezone', config('app.timezone'));
    }

    /** Start of a calendar day in the operator's timezone, converted to the storage timezone. */
    public static function inicioDoDia(string $data): Carbon
    {
        return Carbon::parse($data, self::fuso())->startOfDay()->setTimezone(config('app.timezone'));
    }

    /** End of a calendar day in the operator's timezone, converted to the storage timezone. */
    public static function fimDoDia(string $data): Carbon
    {
        return Carbon::parse($data, self::fuso())->endOfDay()->setTimezone(config('app.timezone'));
    }

    /** "Now" in the operator's timezone (for month/day boundaries shown on screen). */
    public static function agora(): Carbon
    {
        return Carbon::now(self::fuso());
    }
}
