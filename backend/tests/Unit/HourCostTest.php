<?php

namespace Tests\Unit;

use App\Domain\Costs\HourCost;
use PHPUnit\Framework\TestCase;

class HourCostTest extends TestCase
{
    public function test_convierte_horas_decimales_a_minutos_enteros(): void
    {
        $this->assertSame(150, HourCost::minutesFromHours(2.5));
        $this->assertSame(15, HourCost::minutesFromHours('0.25'));
        $this->assertSame(480, HourCost::minutesFromHours(8));
    }

    public function test_calcula_el_importe_exacto_en_centavos(): void
    {
        $this->assertSame(100000, HourCost::amountCents(40000, 150));
        $this->assertSame(7500, HourCost::amountCents(30000, 15));
        $this->assertSame(56, HourCost::amountCents(3333, 1));
    }

    public function test_formatea_las_horas_del_snapshot(): void
    {
        $this->assertSame('2.50', HourCost::formatHours(150));
        $this->assertSame('0.25', HourCost::formatHours(15));
    }
}
