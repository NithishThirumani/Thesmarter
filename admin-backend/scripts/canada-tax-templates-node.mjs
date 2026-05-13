#!/usr/bin/env node
/**
 * Sample shapes for POST /admin/tax-template; full Canada dataset is seeded via:
 *   php artisan db:seed --class=CanadaTaxTemplatesSeeder
 */

const payloads = [
  { country_code: "CA", region_type: "STATE", region_code: "ON", tax_type: "SALES_TAX", applicability_type: "FLAT", tax_name: "Canada - Ontario HST 13%", components: [{ component_name: "HST", tax_value: 13 }] },
  { country_code: "CA", region_type: "STATE", region_code: "BC", tax_type: "SALES_TAX", applicability_type: "FLAT", tax_name: "Canada - British Columbia GST+PST", components: [{ component_name: "GST", tax_value: 5 }, { component_name: "PST", tax_value: 7 }] },
];

// eslint-disable-next-line no-console
console.table(payloads.map((p) => ({ region_code: p.region_code, tax_name: p.tax_name })));
