-- Additive template-only DDL (preferred path: Laravel migration 2026_04_27_140000_add_jurisdiction_columns_to_tax_master_template.php).
-- Use this only for manual DBA execution; skip columns that already exist.

ALTER TABLE tax_master_template
  ADD COLUMN region_type VARCHAR(20) NOT NULL DEFAULT 'COUNTRY' AFTER country_code,
  ADD COLUMN region_code VARCHAR(20) NULL AFTER region_type,
  ADD COLUMN tax_type VARCHAR(20) NOT NULL DEFAULT 'SALES_TAX' AFTER region_code,
  ADD COLUMN applicability_type VARCHAR(30) NOT NULL DEFAULT 'FLAT' AFTER tax_type;

CREATE INDEX idx_tax_template_country ON tax_master_template (country_code);
CREATE INDEX idx_tax_template_region ON tax_master_template (region_code);

ALTER TABLE tax_master_template
  ADD UNIQUE KEY tax_tpl_country_region_name_unique (country_code, region_code, tax_name);
