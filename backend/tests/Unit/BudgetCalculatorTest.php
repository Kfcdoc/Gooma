<?php

namespace Tests\Unit;

use App\Domain\Costs\BudgetCalculator;
use Database\Seeders\DemoSeeder;
use PHPUnit\Framework\TestCase;

class BudgetCalculatorTest extends TestCase
{
    public function test_el_presupuesto_de_la_seccion_1_7_cuadra_exacto(): void
    {
        // 227,000.00 MXN de costo base (600 h × tarifas propuestas).
        $budget = BudgetCalculator::calculate(22700000, DemoSeeder::plannedExtras());

        $this->assertSame(22700000, $budget['base_cost_cents']);
        $this->assertSame(6810000, $budget['extras_cents']);
        $this->assertSame(29510000, $budget['subtotal_cents']);
        $this->assertSame(4721600, $budget['tax_cents']);
        $this->assertSame(34231600, $budget['total_cents']);
        $this->assertSame('342316.00', BudgetCalculator::format($budget['total_cents']));

        $lines = collect($budget['extras'])->keyBy('code');

        $this->assertSame(2270000, $lines->get('imprevistos')['amount_cents']);
        $this->assertSame(1135000, $lines->get('riesgo_tecnologico')['amount_cents']);
        $this->assertSame(3405000, $lines->get('margen_ganancia')['amount_cents']);
        $this->assertSame(
            BudgetCalculator::BASE_COSTO_BASE,
            $lines->get('margen_ganancia')['applies_to'],
        );
        $this->assertSame(
            BudgetCalculator::BASE_BASE_MAS_EXTRAS,
            $lines->get('iva')['applies_to'],
        );
        $this->assertSame(4721600, $lines->get('iva')['amount_cents']);
    }

    public function test_el_escenario_sin_riesgo_opcional_tambien_cuadra(): void
    {
        $extras = array_map(
            static fn (array $extra): array => $extra['code'] === 'riesgo_tecnologico'
                ? [...$extra, 'active' => false]
                : $extra,
            DemoSeeder::plannedExtras(),
        );

        $budget = BudgetCalculator::calculate(22700000, $extras);

        $this->assertSame(5675000, $budget['extras_cents']);
        $this->assertSame(28375000, $budget['subtotal_cents']);
        $this->assertSame(4540000, $budget['tax_cents']);
        $this->assertSame(32915000, $budget['total_cents']);
        $this->assertSame(32915000, BudgetCalculator::calculate(22700000, DemoSeeder::plannedExtras())['total_without_optional_cents']);
    }
}
