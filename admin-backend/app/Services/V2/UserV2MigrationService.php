<?php

namespace App\Services\V2;

use App\RoleV2;
use App\UserCompanyRoleV2;
use App\UserCredentialV2;
use App\UserLogin;
use App\UserCompanies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserV2MigrationService
{
    /**
     * Copy V1 user_login + user_companies into V2 tables (idempotent for this user).
     */
    public function syncFromV1(UserLogin $login): void
    {
        DB::transaction(function () use ($login) {
            $userId = $login->user_id;

            UserCredentialV2::where('user_id', $userId)->delete();
            UserCompanyRoleV2::where('user_id', $userId)->delete();

            $this->createCredentials($login, $userId);
            $this->createCompanyRoles($login, $userId);
        });
    }

    private function createCredentials(UserLogin $login, int $userId): void
    {
        if (!empty($login->user_mobile)) {
            $mobile = preg_replace('/\D+/', '', (string) $login->user_mobile);
            if ($mobile !== '') {
                UserCredentialV2::create([
                    'user_id' => $userId,
                    'login_type' => 'mobile',
                    'login_value' => $mobile,
                    'password_hash' => $login->user_pin,
                ]);
            }
        }
        if (!empty($login->email)) {
            $email = strtolower(trim((string) $login->email));
            UserCredentialV2::create([
                'user_id' => $userId,
                'login_type' => 'email',
                'login_value' => $email,
                'password_hash' => $login->user_pin,
            ]);
        }
    }

    private function createCompanyRoles(UserLogin $login, int $userId): void
    {
        $mappings = UserCompanies::query()
            ->where('user_id', $userId)
            ->where('status', 1)
            ->get();

        foreach ($mappings as $mapping) {
            $roleId = $this->mapLegacyUserTypeToRoleId((int) $mapping->user_type);
            UserCompanyRoleV2::create([
                'user_id' => $userId,
                'company_id' => (int) $mapping->company_id,
                'role_id' => $roleId,
                'status' => 1,
            ]);
        }
    }

    private function mapLegacyUserTypeToRoleId(int $userType): int
    {
        if ($userType === 3) {
            $id = RoleV2::query()->where('role_name', 'super_user')->value('id');
        } else {
            $id = RoleV2::query()->where('role_name', 'executive')->value('id');
        }
        if ($id === null) {
            throw new \RuntimeException('V2 roles are missing. Run the RolesV2 seeder (roles_v2 table).');
        }

        return (int) $id;
    }
}
