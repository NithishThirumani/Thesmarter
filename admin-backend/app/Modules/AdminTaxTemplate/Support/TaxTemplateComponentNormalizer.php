<?php

namespace App\Modules\AdminTaxTemplate\Support;

use InvalidArgumentException;
use RuntimeException;

/**
 * Normalizes API component payloads (flat tax_value vs dated details) and validates overlaps / positive rates.
 */
class TaxTemplateComponentNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $components
     * @return list<array{component_name: string, details: list<array{tax_value: float, tax_start_date: string, tax_end_date: string|null}>}>
     */
    public static function normalize(array $components): array
    {
        $out = [];
        foreach ($components as $idx => $c) {
            $name = trim((string) ($c['component_name'] ?? ''));
            if ($name === '') {
                throw new InvalidArgumentException('Each component must have a component_name.');
            }

            if (! empty($c['details']) && is_array($c['details'])) {
                $details = [];
                foreach ($c['details'] as $d) {
                    $details[] = self::normalizedDetailRow($d);
                }
            } elseif (isset($c['tax_value'])) {
                $details = [
                    self::normalizedDetailRow([
                        'tax_value' => $c['tax_value'],
                        'tax_start_date' => $c['tax_start_date'] ?? null,
                        'tax_end_date' => $c['tax_end_date'] ?? null,
                    ]),
                ];
            } else {
                throw new InvalidArgumentException("Component \"$name\" requires either details[] or tax_value.");
            }

            foreach ($details as $d) {
                if (($d['tax_value'] ?? 0) <= 0) {
                    throw new InvalidArgumentException("Component \"$name\": tax_value must be greater than 0.");
                }
            }

            self::assertNoOverlappingDateRanges($name, $details);
            $out[] = ['component_name' => $name, 'details' => $details];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array{tax_value: float, tax_start_date: string, tax_end_date: string|null}
     */
    public static function normalizedDetailRow(array $d): array
    {
        $taxValue = isset($d['tax_value']) ? (float) $d['tax_value'] : 0;
        $start = isset($d['tax_start_date']) && $d['tax_start_date'] !== ''
            ? substr((string) $d['tax_start_date'], 0, 10)
            : now()->toDateString();
        $endRaw = isset($d['tax_end_date']) ? trim((string) $d['tax_end_date']) : '';
        $end = $endRaw !== '' ? substr($endRaw, 0, 10) : null;

        return [
            'tax_value' => $taxValue,
            'tax_start_date' => $start,
            'tax_end_date' => $end,
        ];
    }

    /**
     * @param  list<array{tax_value: float, tax_start_date: string, tax_end_date: string|null}>  $detailRows
     */
    private static function assertNoOverlappingDateRanges(string $componentName, array $detailRows): void
    {
        $ranges = [];
        foreach ($detailRows as $r) {
            $s = $r['tax_start_date'];
            if ($s === '') {
                throw new InvalidArgumentException("Component \"$componentName\": invalid tax_start_date.");
            }
            $e = ($r['tax_end_date'] === null || $r['tax_end_date'] === '') ? '9999-12-31' : $r['tax_end_date'];
            if (strcmp((string) $e, (string) $s) < 0) {
                throw new InvalidArgumentException("Component \"$componentName\": tax_end_date must be on or after tax_start_date.");
            }
            $ranges[] = [(string) $s, (string) $e];
        }

        usort($ranges, fn ($a, $b) => strcmp($a[0], $b[0]));
        for ($i = 1; $i < count($ranges); $i++) {
            $prevEnd = $ranges[$i - 1][1];
            $currStart = $ranges[$i][0];
            // Overlap if ranges are not strictly ordered (sharing a boundary day counts as overlap for dated slabs).
            if (strcmp($currStart, $prevEnd) <= 0) {
                throw new RuntimeException("Component \"$componentName\": overlapping tax date ranges are not allowed.");
            }
        }
    }
}
