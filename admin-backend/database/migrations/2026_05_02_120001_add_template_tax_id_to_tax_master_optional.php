<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional traceability: which country template cloned this company's tax slab.
 */
class AddTemplateTaxIdToTaxMasterOptional extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tax_master')) {
            return;
        }

        Schema::table('tax_master', function (Blueprint $table) {
            if (! Schema::hasColumn('tax_master', 'template_tax_id')) {
                $table->unsignedBigInteger('template_tax_id')->nullable()->after('company_id');
                $table->index('template_tax_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tax_master')) {
            return;
        }

        Schema::table('tax_master', function (Blueprint $table) {
            if (Schema::hasColumn('tax_master', 'template_tax_id')) {
                $table->dropColumn('template_tax_id');
            }
        });
    }
}
