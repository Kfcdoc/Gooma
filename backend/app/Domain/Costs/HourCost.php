<?php

namespace App\Domain\Costs;

/**
 * Costo de horas de equipo (RN-03).
 *
 * Las horas se almacenan como minutos enteros y las tarifas como centavos,
 * de modo que `tarifa × tiempo` sea siempre exacto. La tarifa se congela en
 * el movimiento: un cambio posterior en el catálogo no altera el registro.
 */
final class HourCost
{
    /** Horas decimales capturadas → minutos enteros (máximo 2 decimales). */
    public static function minutesFromHours(float|int|string $hours): int
    {
        return (int) round(((float) $hours) * 60);
    }

    /** Importe redondeado a centavos (half up). */
    public static function amountCents(int $rateCents, int $minutes): int
    {
        return intdiv($rateCents * $minutes + 30, 60);
    }

    public static function formatHours(int $minutes): string
    {
        return number_format($minutes / 60, 2, '.', '');
    }
}
