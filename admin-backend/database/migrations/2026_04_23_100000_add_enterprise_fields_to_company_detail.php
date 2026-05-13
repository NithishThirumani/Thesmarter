<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_detail')) {
            return;
        }

        Schema::table('company_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('company_detail', 'legal_name')) {
                $table->string('legal_name', 255)->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('company_detail', 'tag_line')) {
                $table->string('tag_line', 255)->nullable()->after('legal_name');
            }
            if (!Schema::hasColumn('company_detail', 'description')) {
                $table->text('description')->nullable()->after('tag_line');
            }
            if (!Schema::hasColumn('company_detail', 'phone_number')) {
                $table->string('phone_number', 32)->nullable()->after('description');
            }
            if (!Schema::hasColumn('company_detail', 'email')) {
                $table->string('email', 255)->nullable()->after('phone_number');
            }
        });

        Schema::table('company_detail', function (Blueprint $table) {
            $sm = Schema::getConnection()->getSchemaBuilder();
            $hasIndex = false;
            if (method_exists($sm, 'hasIndex')) {
                $hasIndex = $sm->hasIndex('company_detail', 'company_detail_email_unique');
            }
            if (!$hasIndex) {
                try {
                    $table->unique('email', 'company_detail_email_unique');
                } catch (\Throwable $e) {
                    // Existing duplicate data can block unique creation in older environments.
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_detail')) {
            return;
        }

        Schema::table('company_detail', function (Blueprint $table) {
            try {
                $table->dropUnique('company_detail_email_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            foreach (['legal_name', 'tag_line', 'description', 'phone_number', 'email'] as $col) {
                if (Schema::hasColumn('company_detail', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
