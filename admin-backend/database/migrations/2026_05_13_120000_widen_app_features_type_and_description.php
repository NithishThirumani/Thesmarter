<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy installs may use a very short `feature_type` column; long values or driver quirks caused SQLSTATE 22001.
 * Widen columns so admin feature create matches long descriptions (e.g. product copy).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_features')) {
            return;
        }

        try {
            if (Schema::hasColumn('app_features', 'feature_type')) {
                DB::statement('ALTER TABLE `app_features` MODIFY `feature_type` VARCHAR(255) NULL');
            } else {
                Schema::table('app_features', function (Blueprint $table) {
                    $table->string('feature_type', 255)->nullable();
                });
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if (Schema::hasColumn('app_features', 'feature_description')) {
                DB::statement('ALTER TABLE `app_features` MODIFY `feature_description` MEDIUMTEXT NULL');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        // Intentionally no-op: shrinking risks data loss on production DBs.
    }
};
