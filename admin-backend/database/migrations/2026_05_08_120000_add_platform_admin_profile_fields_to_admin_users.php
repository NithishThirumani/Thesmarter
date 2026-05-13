<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_users')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_users', 'name')) {
                $table->string('name', 255)->default('')->after('email');
            }
            if (! Schema::hasColumn('admin_users', 'phone_number')) {
                $table->string('phone_number', 32)->nullable()->after('name');
            }
            if (! Schema::hasColumn('admin_users', 'role')) {
                $table->string('role', 32)->default('super_admin')->after('phone_number');
            }
            if (! Schema::hasColumn('admin_users', 'created_by')) {
                $table->uuid('created_by')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('admin_users', 'updated_by')) {
                $table->uuid('updated_by')->nullable()->after('created_by');
            }
        });

        if (Schema::hasColumn('admin_users', 'name') && Schema::hasColumn('admin_users', 'role')) {
            DB::table('admin_users')->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })->update(['name' => DB::raw('email')]);

            DB::table('admin_users')->where(function ($q) {
                $q->whereNull('role')->orWhere('role', '');
            })->update(['role' => 'super_admin']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_users')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table) {
            $drop = [];
            foreach (['updated_by', 'created_by', 'role', 'phone_number', 'name'] as $col) {
                if (Schema::hasColumn('admin_users', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
