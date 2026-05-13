<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;
use App\Exceptions\BusinessRuleException;
use App\Services\Auth\OtpService;
use App\UserDetail;
use DB;
use Log;
use stdClass;

class UserService
{
    protected $userRepository;
    protected $otpService;
    protected int $pendingRetentionDays = 30; // adjust retention window
    protected int $otpRequestIpLimitWindowSeconds = 60; // optional

    public function __construct(UserRepositoryInterface $userRepository, OtpService $otpService)
    {
        $this->userRepository = $userRepository;
        $this->otpService = $otpService;
    }
    public function getUserProfile($user)
    {
        $userProfile = $this->userRepository->findUserById($user);
        if (!$userProfile) {
            return null;
        }
        return $userProfile;
    }

    public function checkUserAccess($userId)
    {
        $user = $this->userRepository->findUserWithDetailsAndCompanies($userId);
        if (!$user) {
            return false;
        }

        $isActive = $user['user_status'] === 1;
        return $isActive;
    }

    public function registerUser(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Defensive normalization
            if (!empty($data['email']) && is_string($data['email'])) {
                $data['email'] = strtolower(trim($data['email']));
            }
            if (!empty($data['phone']) && is_string($data['phone'])) {
                $data['phone'] = preg_replace('/\D+/', '', $data['phone']);
            }
            $ip = request()->ip() ?? null;
            $companyId = $data['company_id'] ?? null;

            // default active for company registrations, pending for self registrations
            $data['user_status'] = $companyId === null ? 0 : 1;

            // Lookup existing user (by email/phone etc)
            $lookup = $this->lookupUser($data, $companyId);

            // If found mapping or user exists
            if ($lookup['found']) {
                $existingUser = $lookup['user'];

                // Company-driven registration: create mapping if allowed and return user
                if ($companyId !== null) {
                    $this->isMappingAllowed($lookup, $data['user_type'] ?? 'customer');
                    // $this->userRepository->createUserCompanyMapping($existingUser->user_id, $companyId, $data['user_type'] ?? 'customer');
                    return [
                        'status'   => 'mapped',
                        'user'     => $existingUser->load(['login', 'additionalDetails', 'contacts.contactDetails', 'userCompanies']),
                        'otp_sent' => false,
                        'message'  => 'User exists and mapping created/confirmed.'
                    ];
                }

                // Self-registration paths when companyId === null
                $userStatus = (int)($existingUser->details->user_status ?? 0);

                // Active user: block duplicate registration attempts
                if ($userStatus === 1) {
                    // if email/phone collides, return conflict
                    if (!empty($data['phone']) && $existingUser->user_mobile === $data['phone']) {
                        return [
                            'status'   => 'existing',
                            'user'     => null, // return empty object to avoid exposing user data
                            'otp_sent' => false,
                            'message'  => 'This phone already exists.'
                        ];
                    }
                    if (!empty($data['email']) && $existingUser->email === $data['email']) {
                        return [
                            'status'   => 'existing',
                            'user'     => null, // return empty object to avoid exposing user data
                            'otp_sent' => false,
                            'message'  => 'This email already exists.'
                        ];
                    }

                    // if we reach here, it's an active user but no exact collision — return existing as safe default
                    return [
                        'status'   => 'existing',
                        'user'     => $existingUser->load(['login', 'additionalDetails', 'contacts.contactDetails', 'userCompanies']),
                        'otp_sent' => false,
                        'message'  => 'User already exists and is active.'
                    ];
                }

                // Pending/inactive user: allow reactivation / resend OTP (if within retention)
                if ($userStatus === 0) {
                    $createdAt = $existingUser->created_dtm;
                    $now = now();
                    $daysDiff = $createdAt->diffInDays($now);

                    if ($daysDiff > $this->pendingRetentionDays) {
                        // stale pending: delete and continue to create a fresh user below
                        $this->userRepository->deleteUser($existingUser->user_id);
                        // let logic fall through to creation
                    } else {
                        // update limited fields to avoid overwriting sensitive data
                        $updateData = array_filter([
                            'fname' => $data['fname'] ?? $lookup['user']->details->first_name,
                            'lname'  => $data['lname']  ?? $lookup['user']->details->last_name,
                            'dob' => $data['dob'] ?? $lookup['user']->details->user_dob,
                            'marital_status' => $data['marital_status'] ?? $lookup['user']->details->marital_status,
                            'gender' => $data['gender'] ?? $lookup['user']->details->user_gender,
                            'phone' => $lookup['user']->user_mobile,
                            'email' => $lookup['user']->email,
                        ]);

                        $userDetail = $this->userRepository->findUserByIdSimple($existingUser->user_id);
                        $this->userRepository->updateUser($userDetail, $updateData);

                        // Resend OTP (resend limit enforced inside)
                        $emailForOtp = $data['email'] ?? ($existingUser->email ?? null);
                        $this->otpService->sendRegistrationOtp($emailForOtp, $existingUser->user_id, $ip);

                        return [
                            'status'   => 'pending',
                            'user'     => null, // return empty object to avoid exposing user data
                            'otp_sent' => true,
                            'message'  => 'You have a pending registration. A new verification code was sent.'
                        ];
                    }
                }
            }

            // --- Create user (either new or we deleted stale and now recreate) ---
            $user = $lookup['user'] ?? $this->userRepository->createUser($data);

            // If company-driven, create mapping and return immediately active
            if ($companyId !== null) {
                $this->userRepository->createUserCompanyMapping($user->user_id, $companyId, $data['user_type'] ?? 'customer');

                // Additional details
                if (isset($data['additional_details']) && !empty($data['additional_details'])) {
                    $this->userRepository->storeAdditionalDetails($user, $data['additional_details']);
                }

                // Contact info
                if (isset($data['addresses']) && !empty($data['addresses'])) {
                    $this->userRepository->storeContactDetails($user, $data['addresses']);
                }

                return [
                    'status'   => 'created', // created + active for company path
                    'user'     => $user->load(['login', 'additionalDetails', 'contacts.contactDetails', 'userCompanies']),
                    'otp_sent' => false,
                    'message'  => 'User created and mapped to company.'
                ];
            }

            // Self-registration: must have email to send OTP
            $email = $data['email'] ?? ($user->email ?? null);
            if (!$email) {
                // roll back via exception so transaction is aborted
                throw new \RuntimeException('Unable to determine email for OTP delivery.');
            }

            // queue/send OTP
            $this->otpService->sendRegistrationOtp($email, $user->user_id, $ip);

            return [
                'status'   => 'pending',
                'user'     => null, // return empty object to avoid exposing user data
                'otp_sent' => true,
                'message'  => 'User registered. Verification code sent to email.'
            ];
        });
    }
    public function registerUserold(array $data): UserDetail
    {

        return DB::transaction(function () use ($data) {

            // Normalize (defensive in case Request didn't)
            if (!empty($data['email'])) {
                $data['email'] = is_string($data['email']) ? strtolower(trim($data['email'])) : $data['email'];
            }
            if (!empty($data['phone'])) {
                $data['phone'] = is_string($data['phone']) ? preg_replace('/\D+/', '', $data['phone']) : $data['phone'];
            }
            $ip = request()->ip() ?? null;

            // company_id may be missing for self-registration
            $companyId = $data['company_id'] ?? null;

            $data['user_status'] =  1; // default active unless self-reg
            // 
            // Check existing user or mapping
            $lookup = $this->lookupUser($data, $companyId);

            if ($lookup['found']) {
                if ($companyId == null) {
                    $user_status = (int)($lookup['user']->details->user_status ?? 0);

                    // Active User : Block New Self-Registration
                    if ($user_status  === 1) {

                        // check if phone already exist 
                        if (!empty($data['phone']) && $lookup['user']->user_mobile === $data['phone']) {
                            throw new \Exception('This phone already exists.');
                        }
                        if (!empty($data['email']) && $lookup['user']->email === $data['email']) {
                            throw new \Exception('This email already exists.');
                        }
                    }

                    // Inactive User : Allow Reactivation via OTP
                    // Pending User : Decide to resume or stale registration
                    if ($user_status  === 0) {

                        $createdAt = $lookup['user']->created_dtm;
                        $now = now();
                        $daysDiff = $createdAt->diffInDays($now);
                        if ($daysDiff > $this->pendingRetentionDays) {
                            // stale pending registration, delete and allow new
                            $this->userRepository->deleteUser($lookup['user']->user_id);
                        } else {
                            // avoid overwriting sensitive fields unless supplied intentionally
                            $updateData = array_filter([
                                'fname' => $data['fname'] ?? $lookup['user']->details->first_name,
                                'lname'  => $data['lname']  ?? $lookup['user']->details->last_name,
                                'dob' => $data['dob'] ?? $lookup['user']->details->user_dob,
                                'marital_status' => $data['marital_status'] ?? $lookup['user']->details->marital_status,
                                'gender' => $data['gender'] ?? $lookup['user']->details->user_gender,
                                'phone' => $lookup['user']->user_mobile,
                                'email' => $lookup['user']->email,
                            ]);
                            $userDetail  = $this->userRepository->findUserByIdSimple($lookup['user']->user_id);
                            $this->userRepository->updateUser($userDetail, $updateData);
                            // Re-send OTP (respects resend limits inside sendRegistrationOtp)
                            $email = $data['email'] ?? ($lookup['user']->email ?? null);
                            $this->otpService->sendRegistrationOtp($email, $lookup['user'], $ip);
                            return array(
                                'user_id' => $lookup['user']->user_id,
                                'error_flag' => true,
                                'message' => 'You have a pending registration. A new verification code was sent.'
                            );
                        }
                    }
                } else {
                    $this->isMappingAllowed($lookup, $data['user_type']);
                    $user = $lookup['user'];
                    return $user->load([
                        'login',
                        'additionalDetails',
                        'contacts.contactDetails',
                        'userCompanies'
                    ]); // User already exists, return it
                }
            }

            // mark as pending/inactive for self-registration
            if ($companyId === null) {
                $data['user_status'] = 0; // pending until OTP verified
            }



            // Create User
            $user = $lookup['user'] ?? $this->userRepository->createUser($data);

            // If this is a company-driven registration, create user-company mapping
            if ($companyId !== null) {
                $this->userRepository->createUserCompanyMapping($user->user_id, $companyId, $data['user_type'] ?? 'customer');
            }

            // Additional details
            if (isset($data['additional_details']) && !empty($data['additional_details'])) {
                $this->userRepository->storeAdditionalDetails($user, $data['additional_details']);
            }

            // Contact info
            if (isset($data['addresses']) && !empty($data['addresses'])) {
                $this->userRepository->storeContactDetails($user, $data['addresses']);
            }

            // If self-registration, send OTP to email (queued)
            if ($companyId === null) {
                // ensure we pass the email (use login relation if createUser stored email on login)
                $email = $data['email'] ?? ($user->login->email ?? null);
                if (!$email) {
                    // defensive: if no email is present, rollback via exception
                    throw \Exception('Unable to determine email for OTP delivery.');
                }

                // send the OTP (this method should queue the mail and return the EmailOtp record)
                $this->sendRegistrationOtp($email, $user, $ip);
            }
            return $user->load([
                'login',
                'additionalDetails',
                'contacts.contactDetails',
                'userCompanies'
            ]);
        });
    }
    public function getUserDetails(UserDetail $user): UserDetail
    {
        return $this->userRepository->findUserById($user);
    }

    public function updateBasicDetails(UserDetail $user, array $data, bool $isSuperAdmin): UserDetail
    {
        return DB::transaction(function () use ($user, $data, $isSuperAdmin) {
            $newData['identifier'] = $data['phone'];
            $newData['company_id'] = $data['company_id'];
            $newData['type'] = 'phone';

            $lookup = $this->lookupUser($newData);
            if ($lookup['found']) {
                $loookupUser = $lookup['user'];
                if ($loookupUser->user_id !== $user->user_id) {
                    throw ValidationException::withMessages([
                        'phone' => ['This phone number is already used by another user.']
                    ]);
                }
            }

            $this->userRepository->updateUser($user, $data);

            // //  Role conversion for super admin
            // if ($isSuperAdmin && isset($data['convert_to_role'])) {
            //     $this->handleRoleConversion($user, $companyId, $lookup, $data['convert_to_role']);
            // }
            return $user->load(['login']);
        });
    }
    public function updateAdditionalDetails(UserDetail $user, array $data): UserDetail
    {
        return DB::transaction(function () use ($user, $data) {
            // Additional details
            if (!empty($data['additional_details'])) {
                $this->userRepository->storeAdditionalDetails($user, $data['additional_details']);
            }
            return $user->load(['additionalDetails']);
        });
    }


    protected function handleRoleConversion(UserDetail $user, $companyId, $lookup, $newRole)
    {
        $currentMapping = $this->userRepository->getMapping($user->user_id, $companyId);

        if (!$currentMapping) {
            throw ValidationException::withMessages([
                'convert_to_role' => ['User is not mapped to this company.']
            ]);
        }

        if ($currentMapping->type === $newRole) {
            return; // Already same role
        }

        if ($newRole === 'employee' && !$lookup['can_be_employee']) {
            throw ValidationException::withMessages([
                'convert_to_role' => ['User cannot be converted to employee.']
            ]);
        }

        if ($newRole === 'customer' && !$lookup['can_be_customer']) {
            throw ValidationException::withMessages([
                'convert_to_role' => ['User cannot be converted to customer.']
            ]);
        }

        $currentMapping->update(['type' => $newRole]);
    }



    private function isMappingAllowed(array $lookup, string $userType): void
    {
        if (!empty($lookup['is_mapped_to_company'])) {
            throw new BusinessRuleException("User already registered with this company.");
        }

        if ($userType === "employee" && empty($lookup['can_be_employee'])) {
            throw new BusinessRuleException("User cannot be registered as employee. Already active elsewhere.");
        }

        if ($userType === "customer" && empty($lookup['can_be_customer'])) {
            throw new BusinessRuleException("User cannot be registered again as customer.");
        }
    }



    public function lookupUser($data, $companyId = null)
    {
        $user =   $this->userRepository->findByPhoneOrEmail($data);

        if (!$user) {
            return [
                'found' => false,
                'user' => null,
                'addresses' => [],
                'is_mapped_to_company' => false,
                'current_role' => null,
                'can_be_customer' => true,
                'can_be_employee' => true, // allowed until proven otherwise
            ];
        }
        if ($companyId === null) {
            return [
                'found' => true,
                'user' => $user,
                'details' => $user->details,
                'companies' => $user->companies,
                'is_mapped_to_company' => false,
                'current_role' => null,
                'can_be_customer' => true,
                'can_be_employee' => true,
            ];
        }
        $companyMapping = $this->userRepository->userCompanyMapping($user->user_id, $data['company_id']);
        $isMapped = !is_null($companyMapping);
        $existingRole = $companyMapping?->user_type;
        if ($existingRole === 4)
            $existingRole = 'employee';
        elseif ($existingRole === 5)
            $existingRole = 'customer';
        else
            $existingRole = null;

        $isEmployeeElsewhere = $this->userRepository
            ->isActiveEmployeeElsewhere($user->id, $data['company_id']);
        return [
            'found' => true,
            'user' => $user,
            'details' => $user->details,
            'companies' => $user->companies,
            // 'addresses' => $user->addresses()->get(),
            'is_mapped_to_company' => $isMapped,
            'current_role' => $existingRole,
            'can_be_customer' => !$isMapped,
            'can_be_employee' => !$isEmployeeElsewhere && (!$isMapped || $existingRole !== 'employee'),
        ];
    }
    public function getUserListPaginated(
        int $companyId,
        ?string $search,
        string $sortBy,
        string $order,
        int $perPage,
        string $role = 'customer'
    ) {
        if ($role === 'employee') {
            $role = 4; // Employee role ID
        } elseif ($role === 'customer') {
            $role = 5; // Customer role ID
        } else {
            throw new \InvalidArgumentException("Invalid role type provided: $role");
        }

        // Step 1: Build base query
        $query = $this->userRepository
            ->queryUserListBuilder($companyId, $search, $sortBy, $order, $role);

        // Step 2: Paginate with eager loaded mapping
        return $query->paginate($perPage)->through(function ($user) use ($companyId) {
            $data = [
                'identifier' => $user->login->user_mobile,
                'type' => 'phone',
                'company_id' => $companyId
            ];

            $lookup = $this->lookupUser($data);

            return [
                'user_id' => $user->user_id,
                'name' => trim("{$user->first_name} {$user->last_name}"),
                'phone' => $user->login->user_mobile,
                'email' => $user->login->email,
                // 'thumbnail' => $user->thumbnail,
                'is_mapped_to_company' => $lookup['is_mapped_to_company'],
                'current_role' => $lookup['current_role'],
                'can_be_customer' => $lookup['can_be_customer'],
                'can_be_employee' => $lookup['can_be_employee'],
            ];
        });
    }
}
