<?php 

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function findUserByMobile(string $mobile);
    public function findUserWithDetailsAndCompanies($userId);
    public function findByPhoneOrEmail($data);
    public function userCompanyMapping($userId, $companyId);
    public function isActiveEmployeeElsewhere($userId, $companyId): bool;
}
