<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesV2Seeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['role_name' => 'platform_admin', 'role_type' => 'system', 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['role_name' => 'super_user', 'role_type' => 'system', 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['role_name' => 'executive', 'role_type' => 'system', 'company_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ];
        foreach ($rows as $row) {
            DB::table('roles_v2')->updateOrInsert(
                ['role_name' => $row['role_name']],
                $row
            );
        }
    }
}
