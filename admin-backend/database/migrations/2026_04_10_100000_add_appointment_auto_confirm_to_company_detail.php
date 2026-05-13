<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds appointment_auto_confirm (depends on customer_app in UI).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_detail')) {
            return;
        }
        Schema::table('company_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('company_detail', 'appointment_auto_confirm')) {
                $table->boolean('appointment_auto_confirm')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_detail')) {
            return;
        }
        Schema::table('company_detail', function (Blueprint $table) {
            if (Schema::hasColumn('company_detail', 'appointment_auto_confirm')) {
                $table->dropColumn('appointment_auto_confirm');
            }
        });
    }
};
