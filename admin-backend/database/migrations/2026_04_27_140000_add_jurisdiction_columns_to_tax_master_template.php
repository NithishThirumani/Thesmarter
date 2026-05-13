<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jurisdiction categorization on template master only — live tax_* tables unchanged.
 */
class AddJurisdictionColumnsToTaxMasterTemplate extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tax_master_template')) {
            return;
        }

        Schema::table('tax_master_template', function (Blueprint $table) {
            if (! Schema::hasColumn('tax_master_template', 'region_type')) {
                $table->string('region_type', 20)->default('COUNTRY')->after('country_code');
            }
            if (! Schema::hasColumn('tax_master_template', 'region_code')) {
                $table->string('region_code', 20)->nullable()->after('region_type');
            }
            if (! Schema::hasColumn('tax_master_template', 'tax_type')) {
                $table->string('tax_type', 20)->default('SALES_TAX')->after('region_code');
            }
            if (! Schema::hasColumn('tax_master_template', 'applicability_type')) {
                $table->string('applicability_type', 30)->default('FLAT')->after('tax_type');
            }
        });

        DB::table('tax_master_template')
            ->where(function ($q) {
                $q->whereNull('tax_type')->orWhere('tax_type', '');
            })
            ->update(['tax_type' => 'SALES_TAX']);

        $tableName = 'tax_master_template';

        if (! $this->indexExists($tableName, 'tax_tpl_country_region_name_unique')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(['country_code', 'region_code', 'tax_name'], 'tax_tpl_country_region_name_unique');
            });
        }

        if (! $this->indexExists($tableName, 'idx_tax_template_country')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('country_code', 'idx_tax_template_country');
            });
        }

        if (! $this->indexExists($tableName, 'idx_tax_template_region')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('region_code', 'idx_tax_template_region');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tax_master_template')) {
            return;
        }

        $tableName = 'tax_master_template';

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if ($this->indexExists($tableName, 'idx_tax_template_region')) {
                $table->dropIndex('idx_tax_template_region');
            }
            if ($this->indexExists($tableName, 'idx_tax_template_country')) {
                $table->dropIndex('idx_tax_template_country');
            }
            if ($this->indexExists($tableName, 'tax_tpl_country_region_name_unique')) {
                $table->dropUnique('tax_tpl_country_region_name_unique');
            }
        });

        Schema::table($tableName, function (Blueprint $table) {
            foreach (['applicability_type', 'tax_type', 'region_code', 'region_type'] as $col) {
                if (Schema::hasColumn('tax_master_template', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                $rows = DB::select("PRAGMA index_list(\"{$safe}\")");
                foreach ($rows as $row) {
                    $name = $row->name ?? $row->Name ?? null;
                    if ($name !== null && strcasecmp((string) $name, $indexName) === 0) {
                        return true;
                    }
                }

                return false;
            }

            $database = Schema::getConnection()->getDatabaseName();
            $r = DB::selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $indexName]
            );

            return $r !== null && (int) $r->c > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
