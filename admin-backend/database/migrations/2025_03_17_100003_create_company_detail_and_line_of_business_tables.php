<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fresh installs (e.g. empty Docker DB) had no legacy ERP tables. Later migrations only ALTER many of these.
 * Creates minimal tenant / catalogue / payment shells so admin dashboard, Companies, LOB, and company wizard work.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('line_of_business')) {
            Schema::create('line_of_business', function (Blueprint $table) {
                $table->bigIncrements('lob_id');
                $table->string('lob_name', 255);
                $table->text('lob_description')->nullable();
                $table->string('lob_status', 32)->default('A');
                $table->dateTime('create_dtm')->nullable();
                $table->dateTime('updated_dtm')->nullable();
            });
        }

        if (Schema::hasTable('line_of_business') && DB::table('line_of_business')->count() === 0) {
            DB::table('line_of_business')->insert([
                'lob_name' => 'General',
                'lob_description' => 'Default line of business (seeded for new environments).',
                'lob_status' => 'A',
                'create_dtm' => now(),
                'updated_dtm' => now(),
            ]);
        }

        if (! Schema::hasTable('company_detail')) {
            Schema::create('company_detail', function (Blueprint $table) {
                $table->bigIncrements('company_id');
                $table->string('company_name', 255);
                $table->string('company_status', 32)->default('A');
                $table->unsignedBigInteger('company_business_id')->nullable();
                $table->dateTime('create_dtm')->nullable();
                $table->dateTime('updated_dtm')->nullable();
            });
        }

        if (! Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->bigIncrements('payment_id');
                $table->string('payment_name', 255);
                $table->text('payment_description')->nullable();
                $table->unsignedTinyInteger('active_status')->default(1);
                $table->string('payment_type', 64)->nullable();
                $table->dateTime('create_dtm')->nullable();
            });
        }

        if (! Schema::hasTable('contact_detail')) {
            Schema::create('contact_detail', function (Blueprint $table) {
                $table->bigIncrements('contact_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('phone', 64)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('pincode', 32)->nullable();
                $table->string('city', 128)->nullable();
                $table->string('state', 128)->nullable();
                $table->string('country', 128)->nullable();
                $table->string('address1', 255)->nullable();
                $table->string('area', 128)->nullable();
                $table->decimal('longitude', 11, 7)->nullable();
                $table->decimal('latitude', 11, 7)->nullable();
                $table->dateTime('create_dtm')->nullable();
                $table->dateTime('updated_dtm')->nullable();
            });
        }

        if (! Schema::hasTable('branch_detail')) {
            Schema::create('branch_detail', function (Blueprint $table) {
                $table->bigIncrements('branch_id');
                $table->unsignedBigInteger('company_id');
                $table->string('branch_name', 255)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedTinyInteger('branch_status')->default(1);
                $table->unsignedBigInteger('contact_id')->nullable();
                $table->string('branch_type', 8)->default('S');
                $table->unsignedTinyInteger('work_type')->default(1);
                $table->dateTime('create_dtm')->nullable();
                $table->dateTime('updated_dtm')->nullable();
            });
        }

        if (! Schema::hasTable('merchant_catalogue')) {
            Schema::create('merchant_catalogue', function (Blueprint $table) {
                $table->bigIncrements('catalogue_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('lob_id')->nullable();
                $table->string('catalogue_status', 32)->nullable()->default('A');
            });
        }

        if (! Schema::hasTable('app_features')) {
            Schema::create('app_features', function (Blueprint $table) {
                $table->bigIncrements('feature_id');
                $table->string('feature_name', 255);
                $table->string('feature_type', 255)->nullable();
                $table->mediumText('feature_description')->nullable();
                $table->string('feature_status', 32)->default('A');
            });
        }

        if (! Schema::hasTable('company_features')) {
            Schema::create('company_features', function (Blueprint $table) {
                $table->bigIncrements('cf_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('feature_id');
                $table->unsignedTinyInteger('company_feature_status')->default(1);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_features');
        Schema::dropIfExists('app_features');
        Schema::dropIfExists('merchant_catalogue');
        Schema::dropIfExists('branch_detail');
        Schema::dropIfExists('contact_detail');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('company_detail');
        Schema::dropIfExists('line_of_business');
    }
};
