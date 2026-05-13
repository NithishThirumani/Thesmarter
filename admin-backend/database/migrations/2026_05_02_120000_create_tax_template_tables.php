<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Country-level tax blueprint tables (additive — does not alter tax_* live tables besides optional trace column elsewhere).
 */
class CreateTaxTemplateTables extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tax_master_template')) {
            Schema::create('tax_master_template', function (Blueprint $table) {
                $table->bigIncrements('template_tax_id');
                $table->char('country_code', 2);
                $table->string('tax_name', 255);
                $table->unsignedTinyInteger('is_active')->default(1);
                $table->unsignedInteger('version')->default(1);
                $table->timestamp('created_dtm')->useCurrent();
                $table->timestamp('updated_dtm')->useCurrent()->useCurrentOnUpdate();
                $table->index(['country_code', 'is_active']);
            });
        }

        if (! Schema::hasTable('tax_component_template')) {
            Schema::create('tax_component_template', function (Blueprint $table) {
                $table->bigIncrements('template_tc_id');
                $table->unsignedBigInteger('template_tax_id');
                $table->string('component_name', 255);
                $table->timestamp('created_dtm')->useCurrent();
                $table->timestamp('updated_dtm')->useCurrent()->useCurrentOnUpdate();
                $table->index('template_tax_id');
            });
        }

        if (! Schema::hasTable('tax_detail_template')) {
            Schema::create('tax_detail_template', function (Blueprint $table) {
                $table->bigIncrements('template_td_id');
                $table->unsignedBigInteger('template_tc_id');
                $table->decimal('tax_value', 12, 4)->default(0);
                $table->date('tax_start_date');
                $table->date('tax_end_date')->nullable();
                $table->timestamp('created_dtm')->useCurrent();
                $table->timestamp('updated_dtm')->useCurrent()->useCurrentOnUpdate();
                $table->index('template_tc_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_detail_template');
        Schema::dropIfExists('tax_component_template');
        Schema::dropIfExists('tax_master_template');
    }
}
