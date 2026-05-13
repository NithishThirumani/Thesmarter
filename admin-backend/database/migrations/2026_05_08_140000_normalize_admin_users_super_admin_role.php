<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize legacy platform_admin rows so all portal admins use super_admin only.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admin_users')) {
            return;
        }

        DB::table('admin_users')->where('role', 'platform_admin')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        // Non-reversible without storing prior roles.
    }
};
