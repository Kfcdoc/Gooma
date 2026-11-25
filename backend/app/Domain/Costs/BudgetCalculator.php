<?php

namespace App\Domain\Costs;

/**
 * Cálculo del presupuesto de planeación de la sección 1.7 de docs/Gooma.md.
 *
 * Reglas aplicadas:
 *  - RN-15: todos los extras se calculan sobre el costo base, salvo el IVA,
 *    que se calcula sobre base + extras.
 *  - Decisión del equipo: el margen del 15% es una línea de extras más sobre
 *    el costo base (no un margen sobre ventas).
 *  - Imprevistos y riesgo son extras de base; el IVA es el único extra que
 *    toma como base el subtotal.
 *
 * Los montos se manejan en centavos enteros para que el seeder cuadre exacto
 * (342,316.00 MXN) sin errores de coma flotante.
 */
final class BudgetCalculator
{
    public const EXTRA_NECESARIO = 'necesario';

    public const EXTRA_OPCIONAL = 'opcional';

    /** Extra calculado sobre el costo base. */
    public const BASE_COSTO_BASE = 'costo_base';

    /** Extra calculado sobre base + extras (IVA). */
    public const BASE_BASE_MAS_EXTRAS = 'base_mas_extras';

    /**
     * @param  list<array{code:string,name:string,percent:int,type:string,applies_to:string,active:bool}>  $extras
     * @return array{
     *     base_cost_cents:int,
     *     extras:list<array<string,mixed>>,
     *     extras_cents:int,
     *     subtotal_cents:int,
     *     tax_cents:int,
     *     total_cents:int,
     *     total_without_optional_cents:int
     * }
     */
    public static function calculate(int $baseCostCents, array $extras): array
    {
        $current = self::summarize($baseCostCents, $extras);

        $withoutOptional = array_map(
            static fn (array $extra): array => $extra['type'] === self::EXTRA_OPCIONAL
                ? [...$extra, 'active' => false]
                : $extra,
            $extras,
        );

        return [
            'base_cost_cents' => $baseCostCents,
            'extras' => $current['lines'],
            'extras_cents' => $current['extras_cents'],
            'subtotal_cents' => $current['subtotal_cents'],
            'tax_cents' => $current['tax_cents'],
            'total_cents' => $current['total_cents'],
            'total_without_optional_cents' => self::summarize($baseCostCents, $withoutOptional)['total_cents'],
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $extras
     * @return array{lines:list<array<string,mixed>>,extras_cents:int,subtotal_cents:int,tax_cents:int,total_cents:int}
     */
    private static function summarize(int $baseCostCents, array $extras): array
    {
        $active = array_values(array_filter(
            $extras,
            static fn (array $extra): bool => (bool) ($extra['active'] ?? true),
        ));

        $baseExtras = array_filter(
            $active,
            static fn (array $extra): bool => ($extra['applies_to'] ?? self::BASE_COSTO_BASE) !== self::BASE_BASE_MAS_EXTRAS,
        );

        $taxExtras = array_filter(
            $active,
            static fn (array $extra): bool => ($extra['applies_to'] ?? self::BASE_COSTO_BASE) === self::BASE_BASE_MAS_EXTRAS,
        );

        $extrasCents = 0;
        foreach ($baseExtras as $extra) {
            $extrasCents += self::percent($baseCostCents, (int) $extra['percent']);
        }

        $subtotalCents = $baseCostCents + $extrasCents;

        $taxCents = 0;
        foreach ($taxExtras as $extra) {
            $taxCents += self::percent($subtotalCents, (int) $extra['percent']);
        }

        $lines = [];
        foreach ($extras as $extra) {
            $isActive = (bool) ($extra['active'] ?? true);
            $isTax = ($extra['applies_to'] ?? self::BASE_COSTO_BASE) === self::BASE_BASE_MAS_EXTRAS;

            $lines[] = [
                'code' => $extra['code'],
                'name' => $extra['name'],
                'percent' => (int) $extra['percent'],
                'type' => $extra['type'],
                'applies_to' => $extra['applies_to'] ?? self::BASE_COSTO_BASE,
                'active' => $isActive,
                'amount_cents' => $isActive
                    ? self::percent($isTax ? $subtotalCents : $baseCostCents, (int) $extra['percent'])
                    : 0,
            ];
        }

        return [
            'lines' => $lines,
            'extras_cents' => $extrasCents,
            'subtotal_cents' => $subtotalCents,
            'tax_cents' => $taxCents,
            'total_cents' => $subtotalCents + $taxCents,
        ];
    }

    /** Porcentaje redondeado a centavos (half up). */
    private static function percent(int $baseCents, int $percent): int
    {
        return intdiv($baseCents * $percent + 50, 100);
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
