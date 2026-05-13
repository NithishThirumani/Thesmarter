<?php

namespace App\Repositories;

use App\UserDetail;
use App\UserLogin;
use App\UserCompanies;
use App\UserAdditionalDetail;
use App\UserContact;
use App\ContactDetail;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\User;
use Illuminate\Support\Facades\Hash;

class UsersRepository implements UserRepositoryInterface
{
    public function findUserById(UserDetail $user): UserDetail
    {
        return $user->load([
            'login',
            'additionalDetails',
            'contacts.contactDetails',
            'userCompanies'
        ]);
    }
    public function findUserByMobile(string $mobile)
    {
        return UserLogin::where('user_mobile', $mobile)->first();
    }
    public function findUserByEmail(string $email)
    {
        return UserLogin::where('email', $email)->first();
    }
    public function findUserByIdSimple(int $userId): ?UserDetail
    {
        return UserDetail::with('login')->where('user_id', $userId)->first();
    }

    public function findUserWithDetailsAndCompanies($userId)
    {
        // Find the user details by identifier
        $userDetail = UserDetail::query()
            ->where('user_id', $userId)
            ->orWhereHas('login', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->first();
        if (!$userDetail) {
            return false; // User not found
        }
        // Fetch user login details 
        $userLogin = UserLogin::query()
            ->where('user_id', $userDetail->user_id)
            ->first();
        $userDetail->phone =  $userLogin->user_mobile;
        $userDetail->email = $userLogin->email;


        // Fetch related user companies with user_type 3 or 4
        $userCompanies = UserCompanies::query()
            ->with(['company'])
            ->where('user_id', $userDetail->user_id)
            ->where('status', 1)
            ->whereIn('user_type', [4])
            ->get();
        if ($userCompanies->isEmpty()) {
            return false;    // User not active at any company pany 
        }

        return array_merge(
            $userDetail->toArray(),
            ['user_companies' => $userCompanies->toArray()]
        );
    }
    public function deactivateUser(string $userId)
    {
        return DB::transaction(function () use ($userId) {
            $user = $this->findUserWithDetailsAndCompanies($userId);
            if (!$user) {
                throw new \Exception('User not found.');
            }

            $user->update(['status' => 0, 'deactivated_at' => now()]);
            $user->userLogin->tokens()->delete();
        });
    }
    public function createUser(array $data): UserDetail
    {
        $user =  UserDetail::create([
            'first_name' => $data['fname'],
            'last_name' => $data['lname'],
            'user_dob' => $data['dob'] ?? null,
            'user_gender' => $data['gender'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'user_status' => $data['user_status'], // Active by default
        ]);
        // Generate temp MPIN
        $tempMpin = rand(1000, 9999); // or use Str::random(6) for alpha-numeric
        $this->createTempLogin($user, $data, $tempMpin);

        return $user;
    }
    private function createTempLogin(UserDetail $user, $data, string $rawMpin): void
    {
        UserLogin::create([
            'user_id' => $user->user_id,
            'user_mobile' => $data['phone'],
            'email' => $data['email'],
            'user_pin' => Hash::make($rawMpin),
            'is_temp_mpin' => true,
        ]);
    }
    public function createUserCompanyMapping(int $userId, int $companyId, string $userType = "customer"): void
    {
        $type = 5;  // Default to customer
        if (isset($data['user_type']) && $data['user_type'] === 'employee') {
            $type = 4; // Employee
        }
        UserCompanies::create([
            'user_id' => $userId,
            'company_id' => $companyId,
            'user_type' => $type,
            'status' => 1 // Active by default
        ]);
    }
    public function updateMpin(UserDetail $user, string $newMpin): void
    {
        $login = UserLogin::where('user_id', $user->user_id)->firstOrFail();
        $login->update([
            'mpin_hash' => Hash::make($newMpin),
            'is_temp_mpin' => false,
        ]);
    }
    public function storeAdditionalDetails(UserDetail $user, array $details): void
    {
        UserAdditionalDetail::updateOrCreate(
            ['user_id' => $user->user_id],
            ['details' => $details]
        );
    }
    public function storeContactDetails(UserDetail $user, array $addresses): void
    {
        foreach ($addresses as $address) {
            $contact = ContactDetail::create([
                'phone' => $user->login->user_mobile,
                'email' => $user->login->email ?? null,
                'address' => $address['address'],
                'area' => $address['area'],
                'city' => $address['city'],
                'state' => $address['state'],
                'country' => $address['country'],
                'pincode' => $address['pin_code'],
                'lat' => $address['lat'] ?? 0.00,
                'lng' => $address['lng'] ?? 0.00,
            ]);
            UserContact::create([
                'user_id' => $user->user_id,
                'contact_id' => $contact->contact_id,
                'contact_type' => $address['type'],
                'is_primary' => $address['is_primary'],
            ]);
        }
    }
    public function updateUser(UserDetail $user, array $data): void
    {
        $user->update(
            [
                'first_name' => $data['fname'],
                'last_name' => $data['lname'],
                'user_dob' => $data['dob'],
                'marital_status' => $data['marital_status'],
                'user_gender' => $data['gender']
            ]
        );
        $user->login->update(
            [
                'user_mobile' => $data['phone'],
                'email' => $data['email'],
            ]
        );
    }
    public function deleteUser(int $userId): void
    {
        UserDetail::where('user_id', $userId)->delete();
    }





    public function lookupUser($data)
    {
        $query = UserLogin::query()->with(['details']);
        switch ($data['type']) {
            case 'phone':
                $query->where('user_mobile', $data['identifier']);
                break;
            case 'email':
                $query->where('email', $data['identifier']);
                break;
        }
        $user = $query->first();
        if (!$user || !$user->details) {
            return false;
        }
        $mappings = $user->companies()->get(['company_id', 'user_type', 'status']);
        $canBeEmployee = true;
        foreach ($mappings as $map) {
            if ($map->user_type === 4 && $map->status === 1) {
                $canBeEmployee = false;
                break;
            }
        }
        $user->mappings = $mappings;
        $user->can_register_as_employee = $canBeEmployee;
        $user->can_register_as_customer = true;
        return $user;
    }
    public function findByPhoneOrEmail($data)
    {
        $query = UserLogin::query()->with(['details']);
        if (!empty($data['phone'])) {
            $query->orWhere('user_mobile', $data['phone']);
        }

        if (!empty($data['email'])) {
            $query->orWhere('email', $data['email']);
        }

        $user = $query->first();
        if (!$user || !$user->details) {
            return false;
        }
        return $user;
    }
    public function userCompanyMapping($userId, $companyId): ?UserCompanies
    {

        return UserCompanies::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();
    }
    public function isActiveEmployeeElsewhere($userId, $companyId): bool
    {
        return UserCompanies::query()
            ->where('user_id', $userId)
            ->where('company_id', '!=', $companyId)
            ->where('user_type', 4)
            ->where('status', 1)
            ->exists();
    }
    public function queryUserListBuilder(
        int $companyId,
        ?string $search,
        string $sortBy,
        string $order,
        int $userType
    ) {
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc'; // whitelist

        $query = UserDetail::query()
            ->with(['login'])
            ->whereHas('userCompanies', function ($q) use ($companyId, $userType) {
                $q->where('company_id', $companyId)
                    ->where('status', 1)
                    ->when($userType, fn($q) => $q->where('user_type', $userType));
            });

        if ($search) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$like])

                    // phone/email via login
                    ->orWhereHas('login', function ($subQ) use ($like) {
                        $subQ->where('user_mobile', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })

                    // contact details on the PIVOT table user_contact
                    ->orWhereHas('contacts.contact', function ($subQ) use ($like) {
                        $subQ->where('address', 'like', $like)
                            ->orWhere('area', 'like', $like)
                            ->orWhere('state', 'like', $like);
                    });
            });
        }

        match ($sortBy) {
            'alphabetical' => $query->orderByRaw("CONCAT_WS(' ', first_name, last_name) {$order}"),
            'tags'         => $query->withCount('tags')->orderBy('tags_count', $order),
            default        => $query->orderBy('created_dtm', $order),
        };

        return $query;
    }
}
