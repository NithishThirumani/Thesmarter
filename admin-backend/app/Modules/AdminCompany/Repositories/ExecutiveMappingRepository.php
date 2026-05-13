<?php

namespace App\Modules\AdminCompany\Repositories;

use App\CompanyDetail;
use App\Modules\AdminCompany\Services\SuperUserService;
use App\UserCompanies;

class ExecutiveMappingRepository
{
    public function companyExists(int $companyId): bool
    {
        return CompanyDetail::query()->where('company_id', $companyId)->exists();
    }

    public function executiveMappingExists(int $companyId, int $userId): bool
    {
        return UserCompanies::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('user_type', SuperUserService::USER_TYPE_EXECUTIVE)
            ->exists();
    }

    public function activeExecutiveMappingExists(int $companyId, int $userId): bool
    {
        return UserCompanies::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('user_type', SuperUserService::USER_TYPE_EXECUTIVE)
            ->where('status', 1)
            ->exists();
    }
}
