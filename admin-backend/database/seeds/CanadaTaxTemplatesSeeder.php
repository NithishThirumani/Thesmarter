<?php

namespace Database\Seeders;

use App\TaxComponentTemplate;
use App\TaxDetailTemplate;
use App\TaxMasterTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds Canada provincial/territorial retail tax templates (template tables only).
 * Idempotent via updateOrCreate on (country_code, region_code, tax_name).
 *
 * php artisan db:seed --class=CanadaTaxTemplatesSeeder
 */
class CanadaTaxTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tax_master_template')) {
            $this->command?->warn('tax_master_template missing — skip Canada tax seeds.');

            return;
        }
        if (! Schema::hasColumn('tax_master_template', 'tax_type')) {
            $this->command?->warn('Run migrations for jurisdiction columns — skip Canada tax seeds.');

            return;
        }

        $start = date('Y-m-d');

        $templates = [
            // HST
            ['region' => 'ON', 'tax_name' => 'Canada - Ontario HST 13%', 'tax_type' => 'SALES_TAX', 'components' => [['HST', 13.0]]],
            ['region' => 'NB', 'tax_name' => 'Canada - New Brunswick HST 15%', 'tax_type' => 'SALES_TAX', 'components' => [['HST', 15.0]]],
            ['region' => 'NL', 'tax_name' => 'Canada - Newfoundland and Labrador HST 15%', 'tax_type' => 'SALES_TAX', 'components' => [['HST', 15.0]]],
            ['region' => 'NS', 'tax_name' => 'Canada - Nova Scotia HST 15%', 'tax_type' => 'SALES_TAX', 'components' => [['HST', 15.0]]],
            ['region' => 'PE', 'tax_name' => 'Canada - Prince Edward Island HST 15%', 'tax_type' => 'SALES_TAX', 'components' => [['HST', 15.0]]],
            // GST + PST / RST
            ['region' => 'BC', 'tax_name' => 'Canada - British Columbia GST+PST', 'tax_type' => 'SALES_TAX', 'components' => [['GST', 5.0], ['PST', 7.0]]],
            ['region' => 'SK', 'tax_name' => 'Canada - Saskatchewan GST+PST', 'tax_type' => 'SALES_TAX', 'components' => [['GST', 5.0], ['PST', 6.0]]],
            ['region' => 'MB', 'tax_name' => 'Canada - Manitoba GST+PST', 'tax_type' => 'SALES_TAX', 'components' => [['GST', 5.0], ['PST', 7.0]]],
            // Québec
            ['region' => 'QC', 'tax_name' => 'Canada - Quebec GST+QST', 'tax_type' => 'SALES_TAX', 'components' => [['GST', 5.0], ['QST', 9.975]]],
            // GST-only
            ['region' => 'AB', 'tax_name' => 'Canada - Alberta GST', 'tax_type' => 'GST', 'components' => [['GST', 5.0]]],
            ['region' => 'NT', 'tax_name' => 'Canada - Northwest Territories GST', 'tax_type' => 'GST', 'components' => [['GST', 5.0]]],
            ['region' => 'YT', 'tax_name' => 'Canada - Yukon GST', 'tax_type' => 'GST', 'components' => [['GST', 5.0]]],
            ['region' => 'NU', 'tax_name' => 'Canada - Nunavut GST', 'tax_type' => 'GST', 'components' => [['GST', 5.0]]],
        ];

        foreach ($templates as $row) {
            DB::transaction(function () use ($row, $start) {
                $region = strtoupper($row['region']);
                /** @var \App\TaxMasterTemplate $m */
                $m = TaxMasterTemplate::updateOrCreate(
                    [
                        'country_code' => 'CA',
                        'region_code' => $region,
                        'tax_name' => $row['tax_name'],
                    ],
                    [
                        'region_type' => 'STATE',
                        'tax_type' => $row['tax_type'],
                        'applicability_type' => 'FLAT',
                        'is_active' => 1,
                        'version' => 1,
                    ]
                );

                $tcIds = TaxComponentTemplate::where('template_tax_id', $m->template_tax_id)->pluck('template_tc_id');
                if ($tcIds->isNotEmpty()) {
                    TaxDetailTemplate::whereIn('template_tc_id', $tcIds)->delete();
                    TaxComponentTemplate::whereIn('template_tc_id', $tcIds)->delete();
                }

                foreach ($row['components'] as [$name, $rate]) {
                    $tc = TaxComponentTemplate::create([
                        'template_tax_id' => $m->template_tax_id,
                        'component_name' => $name,
                    ]);
                    TaxDetailTemplate::create([
                        'template_tc_id' => $tc->template_tc_id,
                        'tax_value' => $rate,
                        'tax_start_date' => $start,
                        'tax_end_date' => null,
                    ]);
                }
            });
        }

        $this->command?->info('Canada tax templates seeded: '.count($templates).' jurisdictions.');
    }
}
