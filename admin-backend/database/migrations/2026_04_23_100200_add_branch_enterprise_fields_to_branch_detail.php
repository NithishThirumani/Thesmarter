<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branch_detail')) {
            return;
        }

        // Some legacy rows contain zero-dates; strict SQL mode can block ALTER TABLE.
        DB::statement("SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
        if (!Schema::hasColumn('branch_detail', 'branch_name')) {
            DB::statement("ALTER TABLE `branch_detail` ADD COLUMN `branch_name` varchar(255) NULL AFTER `company_id`");
        }
        if (!Schema::hasColumn('branch_detail', 'latitude')) {
            DB::statement("ALTER TABLE `branch_detail` ADD COLUMN `latitude` decimal(10,7) NULL AFTER `branch_name`");
        }
        if (!Schema::hasColumn('branch_detail', 'longitude')) {
            DB::statement("ALTER TABLE `branch_detail` ADD COLUMN `longitude` decimal(10,7) NULL AFTER `latitude`");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('branch_detail')) {
            return;
        }

        foreach (['branch_name', 'latitude', 'longitude'] as $col) {
            if (Schema::hasColumn('branch_detail', $col)) {
                DB::statement("ALTER TABLE `branch_detail` DROP COLUMN `{$col}`");
            }
        }
    }
};
