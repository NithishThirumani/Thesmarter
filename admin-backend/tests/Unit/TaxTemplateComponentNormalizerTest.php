<?php

namespace Tests\Unit;

use App\Modules\AdminTaxTemplate\Support\TaxTemplateComponentNormalizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TaxTemplateComponentNormalizerTest extends TestCase
{
    public function test_normalizes_flat_tax_value_into_single_detail(): void
    {
        $out = TaxTemplateComponentNormalizer::normalize([
            ['component_name' => 'HST', 'tax_value' => 13],
        ]);
        self::assertCount(1, $out);
        self::assertSame('HST', $out[0]['component_name']);
        self::assertSame(13.0, $out[0]['details'][0]['tax_value']);
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2}$/', $out[0]['details'][0]['tax_start_date']);
    }

    public function test_rejects_non_positive_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TaxTemplateComponentNormalizer::normalize([
            ['component_name' => 'GST', 'tax_value' => 0],
        ]);
    }

    public function test_detects_overlap(): void
    {
        $this->expectException(RuntimeException::class);
        TaxTemplateComponentNormalizer::normalize([
            [
                'component_name' => 'X',
                'details' => [
                    ['tax_value' => 1, 'tax_start_date' => '2020-01-01', 'tax_end_date' => '2020-01-31'],
                    ['tax_value' => 1, 'tax_start_date' => '2020-01-15', 'tax_end_date' => '2020-02-01'],
                ],
            ],
        ]);
    }

    public function test_gst_pst_components_do_not_conflict_across_components(): void
    {
        $out = TaxTemplateComponentNormalizer::normalize([
            ['component_name' => 'GST', 'tax_value' => 5],
            ['component_name' => 'PST', 'tax_value' => 7],
        ]);
        self::assertCount(2, $out);
        self::assertSame(5.0, $out[0]['details'][0]['tax_value']);
        self::assertSame(7.0, $out[1]['details'][0]['tax_value']);
    }

    public function test_touching_dates_do_not_overlap(): void
    {
        $out = TaxTemplateComponentNormalizer::normalize([
            [
                'component_name' => 'T',
                'details' => [
                    ['tax_value' => 1, 'tax_start_date' => '2020-01-01', 'tax_end_date' => '2020-01-31'],
                    ['tax_value' => 2, 'tax_start_date' => '2020-02-01', 'tax_end_date' => null],
                ],
            ],
        ]);
        self::assertSame(2.0, $out[0]['details'][1]['tax_value']);
    }
}
