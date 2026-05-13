<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_payments')) {
            return;
        }

        Schema::table('company_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('company_payments', 'merchant_id')) {
                $table->string('merchant_id', 255)->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('company_payments', 'secret_key_encrypted')) {
                $table->text('secret_key_encrypted')->nullable()->after('merchant_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_payments')) {
            return;
        }

        Schema::table('company_payments', function (Blueprint $table) {
            foreach (['merchant_id', 'secret_key_encrypted'] as $col) {
                if (Schema::hasColumn('company_payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
