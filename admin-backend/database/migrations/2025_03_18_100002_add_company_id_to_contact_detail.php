<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin company module: link contact_detail to company for address/contact per company.
 */
class AddCompanyIdToContactDetail extends Migration
{
    public function up()
    {
        if (Schema::hasTable('contact_detail') && !Schema::hasColumn('contact_detail', 'company_id')) {
            Schema::table('contact_detail', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('contact_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('contact_detail', 'company_id')) {
            Schema::table('contact_detail', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }
}
