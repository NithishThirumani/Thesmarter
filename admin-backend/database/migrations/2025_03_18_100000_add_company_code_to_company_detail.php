<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin company module: add company_code for unique display code (CMP-<ts>-<random>).
 * No changes to existing columns.
 */
class AddCompanyCodeToCompanyDetail extends Migration
{
    public function up()
    {
        if (Schema::hasTable('company_detail') && !Schema::hasColumn('company_detail', 'company_code')) {
            Schema::table('company_detail', function (Blueprint $table) {
                $table->string('company_code', 64)->nullable()->unique()->after('company_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('company_detail', 'company_code')) {
            Schema::table('company_detail', function (Blueprint $table) {
                $table->dropUnique(['company_code']);
                $table->dropColumn('company_code');
            });
        }
    }
}
