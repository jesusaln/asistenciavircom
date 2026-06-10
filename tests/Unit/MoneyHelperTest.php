<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Support\MoneyHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test unitario para MoneyHelper
 * Resuelve Error #24: Falta de Pruebas Unitarias
 */
class MoneyHelperTest extends TestCase
{
    #[Test]
    public function to_cents_converts_dollars_to_cents(): void
    {
        $this->assertEquals(1000, MoneyHelper::toCents(10.00));
        $this->assertEquals(1599, MoneyHelper::toCents(15.99));
        $this->assertEquals(0, MoneyHelper::toCents(0));
        $this->assertEquals(1, MoneyHelper::toCents(0.005)); // Redondeo hacia arriba
    }

    #[Test]
    public function from_cents_converts_cents_to_dollars(): void
    {
        $this->assertEquals(10.00, MoneyHelper::fromCents(1000));
        $this->assertEquals(15.99, MoneyHelper::fromCents(1599));
        $this->assertEquals(0.00, MoneyHelper::fromCents(0));
    }

    #[Test]
    public function round_returns_consistent_precision(): void
    {
        // El helper usa PHP_ROUND_HALF_UP por defecto
        $this->assertEquals(10.56, MoneyHelper::round(10.555, 2));
        $this->assertEquals(10.55, MoneyHelper::round(10.554, 2));
    }

    #[Test]
    public function format_mxn_formats_correctly(): void
    {
        $this->assertEquals('$1,000.00', MoneyHelper::formatMXN(1000));
        $this->assertEquals('$15.50', MoneyHelper::formatMXN(15.5));
        $this->assertEquals('$0.00', MoneyHelper::formatMXN(0));
    }

    #[Test]
    public function is_positive_validates_correctly(): void
    {
        $this->assertTrue(MoneyHelper::isPositive(100));
        $this->assertTrue(MoneyHelper::isPositive(0));
        $this->assertFalse(MoneyHelper::isPositive(-50));
    }

    #[Test]
    public function calculate_iva_computes_correctly(): void
    {
        // IVA del 16%
        $this->assertEquals(16.00, MoneyHelper::calculateIVA(100, 0.16));
        $this->assertEquals(8.00, MoneyHelper::calculateIVA(50, 0.16));

        // IVA del 16% por defecto
        $this->assertEquals(16.00, MoneyHelper::calculateIVA(100));
    }

    #[Test]
    public function apply_discount_computes_correctly(): void
    {
        $this->assertEquals(80.00, MoneyHelper::applyDiscount(100, 20));
        $this->assertEquals(95.00, MoneyHelper::applyDiscount(100, 5));
        $this->assertEquals(100.00, MoneyHelper::applyDiscount(100, 0));
        $this->assertEquals(50.00, MoneyHelper::applyDiscount(100, 50));
    }

    #[Test]
    public function apply_discount_throws_exception_for_invalid_percent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MoneyHelper::applyDiscount(100, 150);
    }

    #[Test]
    public function calculate_margin_computes_correctly(): void
    {
        // Costo 80, Precio 100 = 25% margen
        $this->assertEquals(25.0, MoneyHelper::calculateMargin(80, 100));

        // Costo 50, Precio 100 = 100% margen
        $this->assertEquals(100.0, MoneyHelper::calculateMargin(50, 100));
    }

    #[Test]
    public function calculate_price_with_margin_computes_correctly(): void
    {
        // Costo 80 + 25% margen = 100
        $this->assertEquals(100.00, MoneyHelper::calculatePriceWithMargin(80, 25));
    }

    #[Test]
    public function round_trip_conversion_is_lossless(): void
    {
        $original = 15.99;
        $cents = MoneyHelper::toCents($original);
        $backToFloat = MoneyHelper::fromCents($cents);

        $this->assertEquals($original, $backToFloat);
    }
}
