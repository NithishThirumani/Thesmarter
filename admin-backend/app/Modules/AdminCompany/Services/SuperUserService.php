<?php

namespace App\Modules\AdminCompany\Services;

use App\AppModules;
use App\BranchDetail;
use App\CompanyDetail;
use App\CompanyFeatures;
use App\FeatureModules;
use App\Mail\SuperUserWelcomeMail;
use App\Modules\AdminCompany\Exceptions\ExecutiveConflictNeedsConfirmation;
use App\Support\Mail\PlatformMailConfigurator;
use App\Support\PinGenerator;
use App\UserAccesPermissions;
use App\UserBranchDetail;
use App\UserCompanies;
use App\UserDetail;
use App\UserLogin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Bizwy Executive management ("Super User"): {@see self::USER_TYPE_EXECUTIVE} (user_type = 4).
 *
 * Permissions are stored globally per {@see UserAccesPermissions::user_id}; this service only upserts rows for module IDs derived from company features.
 */
class SuperUserService
{
    public const USER_TYPE_ADMIN = 1;

    public const USER_TYPE_OWNER = 3;

    public const USER_TYPE_EXECUTIVE = 4;

    public const USER_TYPE_CUSTOMER = 5;

    /**
     * Live validation for the create form: normalized mobile + whether it can be used.
     *
     * @return array<string, mixed>
     */
    public function checkMobileAvailability(int $companyId, string $mobileRaw): array
    {
        $company = CompanyDetail::query()->find($companyId);
        if (! $company) {
            throw ValidationException::withMessages(['company_id' => ['Company not found.']]);
        }

        $mobile = $this->normalizeMobile($mobileRaw);
        if (strlen($mobile) < 10) {
            return [
                'normalized' => $mobile,
                'valid_format' => false,
                'registered' => false,
                'available_for_new_account' => false,
                'message' => 'Enter at least 10 digits for a valid mobile number.',
            ];
        }

        $login = UserLogin::query()->where('user_mobile', $mobile)->first();

        if (! $login) {
            return [
                'normalized' => $mobile,
                'valid_format' => true,
                'registered' => false,
                'available_for_new_account' => true,
                'already_super_for_company' => false,
                'message' => 'This number is available. Continue with name and profile details.',
            ];
        }

        $execSame = UserCompanies::query()
            ->where('user_id', $login->user_id)
            ->where('company_id', $companyId)
            ->where('user_type', self::USER_TYPE_EXECUTIVE)
            ->where('status', 1)
            ->first();

        if ($execSame) {
            return [
                'normalized' => $mobile,
                'valid_format' => true,
                'registered' => true,
                'already_super_for_company' => true,
                'available_for_new_account' => false,
                'user_id' => (int) $login->user_id,
                'message' => 'This mobile is already used by an active Executive (Super User) for this company.',
            ];
        }

        return [
            'normalized' => $mobile,
            'valid_format' => true,
            'registered' => true,
            'existing_account' => true,
            'can_link_to_company' => true,
            'available_for_new_account' => false,
            'already_super_for_company' => false,
            'user_id' => (int) $login->user_id,
            'message' => 'This number is registered. Submitting may convert or promote the account for Executive access — follow any confirmation prompts.',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     *                        branch_ids: array<int> (active branches for this company), confirm_convert_owner?, confirm_promote_customer?, permissions?: array<int, array<string, mixed>>
     * @return array{email_sent: bool, user_id?: int|null}
     */
    public function createSuperUser(int $companyId, array $payload): array
    {
        $company = CompanyDetail::query()->find($companyId);
        if (! $company) {
            throw ValidationException::withMessages(['company_id' => ['Company not found.']]);
        }

        $mobile = $this->normalizeMobile((string) $payload['mobile']);
        if ($mobile === '') {
            throw ValidationException::withMessages(['mobile' => ['Enter a valid mobile number.']]);
        }

        $email = strtolower(trim((string) $payload['email']));
        $firstName = trim((string) $payload['first_name']);
        $lastName = isset($payload['last_name']) ? trim((string) $payload['last_name']) : '';
        $detailExtra = $this->detailExtrasFromValidatedPayload($payload);
        $confirmOwner = (bool) ($payload['confirm_convert_owner'] ?? false);
        $confirmCustomer = (bool) ($payload['confirm_promote_customer'] ?? false);
        $permissionsMatrix = $payload['permissions'] ?? null;
        $branchIdsCreate = isset($payload['branch_ids']) && is_array($payload['branch_ids'])
            ? $payload['branch_ids']
            : [];

        $login = UserLogin::query()->where('user_mobile', $mobile)->first();

        if (! $login) {
            $this->validateNewUser($mobile, $email);
            $plainPin = PinGenerator::generateFourDigit();

            $userId = DB::transaction(function () use ($firstName, $lastName, $mobile, $email, $plainPin, $companyId, $detailExtra, $permissionsMatrix, $branchIdsCreate) {
                $uid = $this->createNewUserWithMapping($firstName, $lastName, $mobile, $email, $plainPin, $companyId, $detailExtra);
                $this->syncExecutiveBranches($uid, $companyId, $branchIdsCreate);
                $this->upsertExecutivePermissions($uid, $companyId, is_array($permissionsMatrix) ? $permissionsMatrix : null);

                return $uid;
            });

            $this->sendExecutiveCredentialsMail(
                $this->displayName($firstName, $lastName),
                (string) $company->company_name,
                $mobile,
                $email,
                $plainPin,
                'Welcome to Bizwy — Super User (Executive) access'
            );

            return ['email_sent' => true, 'user_id' => $userId];
        }

        $uid = (int) $login->user_id;
        $this->assertExecutiveCreationRules($uid, $companyId, $confirmOwner, $confirmCustomer);

        $plainPin = PinGenerator::generateFourDigit();

        DB::transaction(function () use ($uid, $companyId, $detailExtra, $permissionsMatrix, $plainPin, $branchIdsCreate) {
            $pair = UserCompanies::query()
                ->where('user_id', $uid)
                ->where('company_id', $companyId)
                ->first();

            if ($pair === null) {
                UserCompanies::query()->create([
                    'user_id' => $uid,
                    'company_id' => $companyId,
                    'user_type' => self::USER_TYPE_EXECUTIVE,
                    'status' => 1,
                    'creator_id' => $uid,
                ]);
            } else {
                $pair->user_type = self::USER_TYPE_EXECUTIVE;
                $pair->status = 1;
                $pair->save();
            }

            $loginRow = UserLogin::query()->findOrFail($uid);
            $loginRow->user_pin = Hash::make($plainPin);
            $loginRow->save();

            $detail = UserDetail::query()->find($uid);
            $this->mergeDetailExtras($detail, $detailExtra);

            $this->syncExecutiveBranches($uid, $companyId, $branchIdsCreate);
            $this->upsertExecutivePermissions($uid, $companyId, is_array($permissionsMatrix) ? $permissionsMatrix : null);
        });

        $detail = UserDetail::query()->find($uid);
        $this->sendExecutiveCredentialsMail(
            $this->displayName((string) ($detail->first_name ?? ''), (string) ($detail->last_name ?? '')),
            (string) $company->company_name,
            $mobile,
            $email,
            $plainPin,
            'Welcome to Bizwy — Super User (Executive) access'
        );

        return ['email_sent' => true, 'user_id' => $uid];
    }

    /**
     * Regenerate PIN and send credentials email (same behaviour as “Resend PIN”).
     */
    public function resendWelcomeEmail(int $companyId, int $userId): void
    {
        $this->resetExecutivePinAndMail($companyId, $userId, 'Your Super User PIN has been reset — Bizwy');
    }

    /**
     * Alias for {@see self::resendWelcomeEmail()} (generate new PIN + email).
     */
    public function resendExecutivePin(int $companyId, int $userId): void
    {
        $this->resendWelcomeEmail($companyId, $userId);
    }

    public function resetExecutivePin(int $companyId, int $userId): void
    {
        $this->resetExecutivePinAndMail($companyId, $userId, 'Your Super User PIN has been reset — Bizwy');
    }

    public function reactivateExecutive(int $companyId, int $userId): void
    {
        $mapping = UserCompanies::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('user_type', self::USER_TYPE_EXECUTIVE)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages(['user_id' => ['Executive mapping not found for this company.']]);
        }

        $mapping->status = 1;
        $mapping->save();
    }

    /**
     * Enabled Executive modules for UI + defaults (Access=Y Read=Y Create=N Update=N Delete=N).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listExecutiveModules(int $companyId): array
    {
        $company = CompanyDetail::query()->find($companyId);
        if (! $company) {
            throw ValidationException::withMessages(['company_id' => ['Company not found.']]);
        }

        $ids = $this->executiveEnabledModuleIds($companyId);
        $out = [];
        foreach ($ids as $mid) {
            $mod = AppModules::query()->find($mid);
            $out[] = [
                'module_id' => $mid,
                'module_name' => $mod ? (string) $mod->module_name : ('Module '.$mid),
                'defaults' => [
                    'Access_priv' => 'Y',
                    'Read_priv' => 'Y',
                    'Create_priv' => 'N',
                    'Update_priv' => 'N',
                    'Delete_priv' => 'N',
                ],
            ];
        }

        return $out;
    }

    /**
     * Paginated executives for a company with optional search & status filter.
     *
     * @param  'all'|'active'|'inactive'  $statusFilter
     */
    public function listForCompany(int $companyId, ?string $search, string $statusFilter, int $perPage, int $page = 1): LengthAwarePaginator
    {
        $perPage = max(5, min(100, $perPage));
        $page = max(1, $page);

        $query = UserCompanies::query()
            ->with(['detail', 'userLogin', 'company'])
            ->where('company_id', $companyId)
            ->where('user_type', self::USER_TYPE_EXECUTIVE);

        if ($statusFilter === 'active') {
            $query->where('status', 1);
        } elseif ($statusFilter === 'inactive') {
            $query->where('status', 0);
        }

        $this->applySuperUserListFilters($query, $search);

        /** @var LengthAwarePaginator<int, UserCompanies> $paginator */
        $paginator = $query->orderByDesc('updated_dtm')->orderByDesc('created_dtm')->paginate($perPage, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function (UserCompanies $row) {
            return $this->serializeMappingRow($row);
        });

        return $paginator;
    }

    /**
     * Paginated executives across all companies (optional single-company filter).
     *
     * @param  'all'|'active'|'inactive'  $statusFilter
     */
    public function listAllSuperUsers(?int $companyId, ?string $search, string $statusFilter, int $perPage, int $page = 1): LengthAwarePaginator
    {
        $perPage = max(5, min(100, $perPage));
        $page = max(1, $page);

        $query = UserCompanies::query()
            ->with(['detail', 'userLogin', 'company'])
            ->where('user_type', self::USER_TYPE_EXECUTIVE);

        if ($companyId !== null && $companyId > 0) {
            $query->where('company_id', $companyId);
        }

        if ($statusFilter === 'active') {
            $query->where('status', 1);
        } elseif ($statusFilter === 'inactive') {
            $query->where('status', 0);
        }

        $this->applySuperUserListFilters($query, $search);

        /** @var LengthAwarePaginator<int, UserCompanies> $paginator */
        $paginator = $query->orderByDesc('updated_dtm')->orderByDesc('created_dtm')->paginate($perPage, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function (UserCompanies $row) {
            return $this->serializeMappingRow($row);
        });

        return $paginator;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\UserCompanies>  $query
     */
    private function applySuperUserListFilters($query, ?string $search): void
    {
        $searchTrim = trim((string) ($search ?? ''));
        if ($searchTrim === '') {
            return;
        }

        $term = '%'.addcslashes($searchTrim, '%_\\').'%';
        $digitsOnly = preg_replace('/\D+/', '', $searchTrim);

        $hasCompanyCode = Schema::hasColumn((new CompanyDetail())->getTable(), 'company_code');

        $query->where(function ($q) use ($term, $digitsOnly, $hasCompanyCode) {
            $q->whereHas('detail', function ($dq) use ($term) {
                $dq->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            })->orWhereHas('userLogin', function ($lq) use ($term, $digitsOnly) {
                $lq->where('email', 'like', $term);
                if ($digitsOnly !== '') {
                    $lq->orWhere('user_mobile', 'like', '%'.$digitsOnly.'%');
                }
            })->orWhereHas('company', function ($cq) use ($term, $hasCompanyCode) {
                $cq->where('company_name', 'like', $term);
                if ($hasCompanyCode) {
                    $cq->orWhere('company_code', 'like', $term);
                }
            });
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForCompany(int $companyId, int $userId): ?array
    {
        $mapping = UserCompanies::query()
            ->with(['detail', 'userLogin', 'company'])
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('user_type', self::USER_TYPE_EXECUTIVE)
            ->first();

        if (! $mapping) {
            return null;
        }

        $base = $this->serializeMappingRow($mapping);
        $base['user_type_label'] = 'Executive (Super User)';
        $base['branches'] = $this->branchAssignmentsForUserCompany($userId, $companyId);
        $base['modules_permissions'] = $this->permissionsSnapshotForExecutiveModules($userId, $companyId);

        return $base;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateForCompany(int $companyId, int $userId, array $payload): void
    {
        $mapping = UserCompanies::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('user_type', self::USER_TYPE_EXECUTIVE)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages([
                'user_id' => ['Executive (Super User) not found for this company.'],
            ]);
        }

        $detail = UserDetail::query()->find($userId);
        $login = UserLogin::query()->find($userId);
        if (! $detail || ! $login) {
            throw ValidationException::withMessages(['user_id' => ['User record is missing.']]);
        }

        $input = [];
        $rules = [];

        if (array_key_exists('first_name', $payload)) {
            $input['first_name'] = $payload['first_name'];
            $rules['first_name'] = 'required|string|max:191';
        }
        if (array_key_exists('last_name', $payload)) {
            $input['last_name'] = $payload['last_name'];
            $rules['last_name'] = 'nullable|string|max:191';
        }
        if (array_key_exists('email', $payload)) {
            $input['email'] = strtolower(trim((string) $payload['email']));
            $rules['email'] = ['required', 'email', 'max:191'];
        }

        if (array_key_exists('gender', $payload)) {
            $input['gender'] = $payload['gender'];
            $rules['gender'] = 'nullable|string|in:M,F,O';
        }
        if (array_key_exists('date_of_birth', $payload)) {
            $input['date_of_birth'] = $payload['date_of_birth'];
            $rules['date_of_birth'] = 'nullable|date';
        }
        if (array_key_exists('marital_status', $payload)) {
            $input['marital_status'] = $payload['marital_status'];
            $rules['marital_status'] = 'nullable|string|in:S,M,D,W,NA';
        }

        $permissionsMatrix = $payload['permissions'] ?? null;

        $hasProfileKeys = $rules !== [];
        $hasPermissions = array_key_exists('permissions', $payload);
        $hasStatus = array_key_exists('status', $payload);

        if (! $hasProfileKeys && ! $hasPermissions && ! $hasStatus) {
            throw ValidationException::withMessages(['payload' => ['Provide at least one field to update.']]);
        }

        if ($rules !== []) {
            Validator::make($input, $rules)->validate();
        }

        if ($hasStatus) {
            Validator::make(
                ['status' => $payload['status']],
                ['status' => 'required|boolean']
            )->validate();
        }

        DB::transaction(function () use ($detail, $login, $payload, $permissionsMatrix, $companyId, $userId, $mapping, $hasStatus) {
            if (array_key_exists('first_name', $payload)) {
                $detail->first_name = $payload['first_name'];
            }
            if (array_key_exists('last_name', $payload)) {
                $detail->last_name = $payload['last_name'];
            }
            if (array_key_exists('gender', $payload)) {
                $v = $payload['gender'];
                $detail->user_gender = ($v !== null && $v !== '') ? (string) $v : null;
            }
            if (array_key_exists('date_of_birth', $payload)) {
                $detail->user_dob = $payload['date_of_birth'] ?: null;
            }
            if (array_key_exists('marital_status', $payload)) {
                $detail->marital_status = $payload['marital_status'] !== null && $payload['marital_status'] !== ''
                    ? (string) $payload['marital_status'] : null;
            }
            $detail->save();

            if (array_key_exists('email', $payload)) {
                $login->email = strtolower(trim((string) $payload['email']));
            }
            $login->save();

            if ($hasStatus) {
                $mapping->status = ((bool) $payload['status']) ? 1 : 0;
                $mapping->save();
            }

            if (is_array($permissionsMatrix)) {
                $this->upsertExecutivePermissions($userId, $companyId, $permissionsMatrix);
            }
        });
    }

    /**
     * Deactivate executive mapping for company (does not delete identity rows).
     */
    public function removeFromCompany(int $companyId, int $userId): void
    {
        $mapping = UserCompanies::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('user_type', self::USER_TYPE_EXECUTIVE)
            ->first();

        if (! $mapping) {
            return;
        }

        $mapping->status = 0;
        $mapping->save();
    }

    private function resetExecutivePinAndMail(int $companyId, int $userId, string $subjectLine): void
    {
        $mapping = UserCompanies::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('user_type', self::USER_TYPE_EXECUTIVE)
            ->where('status', 1)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages(['user_id' => ['Active Executive not found for this company.']]);
        }

        $company = CompanyDetail::query()->find($companyId);
        $detail = UserDetail::query()->find($userId);
        $login = UserLogin::query()->find($userId);
        if (! $company || ! $detail || ! $login) {
            throw ValidationException::withMessages(['user_id' => ['User record is missing.']]);
        }

        $email = strtolower(trim((string) ($login->email ?? '')));
        if ($email === '') {
            throw ValidationException::withMessages(['email' => ['No email on file for this user.']]);
        }

        $plainPin = PinGenerator::generateFourDigit();
        $login->user_pin = Hash::make($plainPin);
        $login->save();

        $this->sendExecutiveCredentialsMail(
            $this->displayName((string) $detail->first_name, (string) $detail->last_name),
            (string) $company->company_name,
            (string) $login->user_mobile,
            $email,
            $plainPin,
            $subjectLine
        );
    }

    private function assertExecutiveCreationRules(int $userId, int $companyId, bool $confirmOwner, bool $confirmCustomer): void
    {
        $active = UserCompanies::query()
            ->where('user_id', $userId)
            ->where('status', 1)
            ->get();

        foreach ($active as $row) {
            if ((int) $row->user_type === self::USER_TYPE_ADMIN) {
                throw ValidationException::withMessages([
                    'mobile' => ['Admin users cannot be registered as Super Users (Executive).'],
                ]);
            }
        }

        foreach ($active as $row) {
            if ((int) $row->company_id !== (int) $companyId && in_array((int) $row->user_type, [self::USER_TYPE_OWNER, self::USER_TYPE_EXECUTIVE], true)) {
                throw ValidationException::withMessages([
                    'mobile' => ['User already exists as Owner or Executive at another company. Deactivate that association first.'],
                ]);
            }
        }

        $sameActive = $active->firstWhere('company_id', $companyId);
        if ($sameActive && (int) $sameActive->user_type === self::USER_TYPE_EXECUTIVE) {
            throw ValidationException::withMessages([
                'mobile' => ['User already exists as Executive for this company.'],
            ]);
        }

        if ($sameActive && (int) $sameActive->user_type === self::USER_TYPE_OWNER && ! $confirmOwner) {
            throw new ExecutiveConflictNeedsConfirmation(
                'OWNER_REQUIRES_CONFIRM',
                'This user already exists as Owner in this company. Convert to Executive (Super User)?',
                ['company_id' => $companyId, 'user_id' => $userId]
            );
        }

        if ($sameActive && (int) $sameActive->user_type === self::USER_TYPE_CUSTOMER && ! $confirmCustomer) {
            throw new ExecutiveConflictNeedsConfirmation(
                'CUSTOMER_REQUIRES_PROMOTE',
                'This user exists as Customer in this company. Promote to Executive (Super User)?',
                ['company_id' => $companyId, 'user_id' => $userId]
            );
        }
    }

    /**
     * @return array<int, int>
     */
    private function executiveEnabledModuleIds(int $companyId): array
    {
        if (! Schema::hasTable('company_features') || ! Schema::hasTable('feature_modules')) {
            throw ValidationException::withMessages([
                'company_id' => ['Company features / module mapping tables are missing in this database.'],
            ]);
        }

        $featureIds = CompanyFeatures::query()
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereRaw('LOWER(TRIM(company_feature_status)) = ?', ['y'])
                    ->orWhere('company_feature_status', 'Y')
                    ->orWhere('company_feature_status', '1');
            })
            ->pluck('feature_id');

        if ($featureIds->isEmpty()) {
            return [];
        }

        $moduleIds = FeatureModules::query()
            ->whereIn('feature_id', $featureIds->all())
            ->where('status', 1)
            ->whereNotNull('module_id')
            ->distinct()
            ->pluck('module_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        return $moduleIds;
    }

    private function mandatoryAccessControlModuleId(): int
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $row = AppModules::query()
            ->where(function ($q) {
                $q->where('module_name', 'like', '%Access Control%')
                    ->orWhere('module_name', 'like', '%access_control%');
            })
            ->orderBy('module_id')
            ->first();

        $cached = $row ? (int) $row->module_id : 37;

        return $cached;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $moduleOverrides
     */
    private function upsertExecutivePermissions(int $userId, int $companyId, ?array $moduleOverrides): void
    {
        $ids = $this->executiveEnabledModuleIds($companyId);
        $mandatory = $this->mandatoryAccessControlModuleId();
        if ($mandatory > 0 && ! in_array($mandatory, $ids, true)) {
            $ids[] = $mandatory;
        }
        $ids = array_values(array_unique(array_filter($ids)));

        $defaults = static fn (): array => [
            'Access_priv' => 'Y',
            'Read_priv' => 'Y',
            'Create_priv' => 'N',
            'Update_priv' => 'N',
            'Delete_priv' => 'N',
        ];

        $overrideIndex = [];
        if (is_array($moduleOverrides)) {
            foreach ($moduleOverrides as $row) {
                if (! isset($row['module_id'])) {
                    continue;
                }
                $overrideIndex[(int) $row['module_id']] = $row;
            }
        }

        foreach ($ids as $mid) {
            $mid = (int) $mid;
            $perms = $defaults();
            if (isset($overrideIndex[$mid])) {
                $ov = $overrideIndex[$mid];
                foreach (['Access_priv', 'Read_priv', 'Create_priv', 'Update_priv', 'Delete_priv'] as $col) {
                    if (array_key_exists($col, $ov)) {
                        $perms[$col] = strtoupper((string) $ov[$col]) === 'Y' ? 'Y' : 'N';
                    }
                }
            }

            UserAccesPermissions::query()->updateOrCreate(
                ['user_id' => $userId, 'module_id' => $mid],
                $perms
            );
        }
    }

    /**
     * Replace branch assignments for this user at this company using explicit branch IDs (must be active).
     *
     * @param  array<int|string>  $branchIds
     */
    private function syncExecutiveBranches(int $userId, int $companyId, array $branchIds): void
    {
        $want = array_values(array_unique(array_filter(array_map('intval', $branchIds))));
        sort($want);

        if ($want === []) {
            throw ValidationException::withMessages([
                'branch_ids' => ['Select at least one branch for this executive.'],
            ]);
        }

        $have = BranchDetail::query()
            ->where('company_id', $companyId)
            ->whereIn('branch_id', $want)
            ->where(function ($q) {
                $q->where('branch_status', 'A')
                    ->orWhere('branch_status', 'a')
                    ->orWhere('branch_status', 1)
                    ->orWhere('branch_status', '1');
            })
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        sort($have);

        if ($want !== $have) {
            throw ValidationException::withMessages([
                'branch_ids' => ['One or more branches are invalid or inactive for this company.'],
            ]);
        }

        UserBranchDetail::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->delete();

        $start = now();
        foreach ($want as $bid) {
            UserBranchDetail::query()->create([
                'user_id' => $userId,
                'branch_id' => $bid,
                'company_id' => $companyId,
                'user_branch_status' => 1,
                'start_date' => $start,
                'end_date' => null,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function branchAssignmentsForUserCompany(int $userId, int $companyId): array
    {
        return UserBranchDetail::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->get()
            ->map(function (UserBranchDetail $row) {
                return [
                    'branch_id' => (int) $row->branch_id,
                    'user_branch_status' => (int) $row->user_branch_status,
                    'start_date' => $row->start_date ? $row->start_date->toIso8601String() : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function permissionsSnapshotForExecutiveModules(int $userId, int $companyId): array
    {
        $ids = $this->executiveEnabledModuleIds($companyId);
        $mandatory = $this->mandatoryAccessControlModuleId();
        if ($mandatory > 0 && ! in_array($mandatory, $ids, true)) {
            $ids[] = $mandatory;
        }
        $ids = array_values(array_unique(array_filter($ids)));

        $out = [];
        foreach ($ids as $mid) {
            $perm = UserAccesPermissions::query()
                ->where('user_id', $userId)
                ->where('module_id', $mid)
                ->first();

            $mod = AppModules::query()->find($mid);
            $out[] = [
                'module_id' => $mid,
                'module_name' => $mod ? (string) $mod->module_name : ('Module '.$mid),
                'Access_priv' => $perm ? (string) $perm->Access_priv : 'Y',
                'Read_priv' => $perm ? (string) $perm->Read_priv : 'Y',
                'Create_priv' => $perm ? (string) $perm->Create_priv : 'N',
                'Update_priv' => $perm ? (string) $perm->Update_priv : 'N',
                'Delete_priv' => $perm ? (string) $perm->Delete_priv : 'N',
            ];
        }

        return $out;
    }

    private function serializeMappingRow(UserCompanies $row): array
    {
        $d = $row->detail;
        $l = $row->userLogin;
        $uid = (int) $row->user_id;

        $c = $row->company;
        $branchCount = UserBranchDetail::query()
            ->where('user_id', $uid)
            ->where('company_id', (int) $row->company_id)
            ->count();

        return [
            'mapping_id' => crc32((string) $uid.'|'.(string) $row->company_id) & 0x7FFFFFFF,
            'user_id' => $uid,
            'company_id' => (int) $row->company_id,
            'company_name' => $c ? (string) ($c->company_name ?? '') : '',
            'company_code' => ($c && isset($c->company_code)) ? (string) $c->company_code : '',
            'first_name' => $d ? (string) $d->first_name : '',
            'last_name' => $d ? (string) $d->last_name : '',
            'gender' => $d && $d->user_gender !== null ? (string) $d->user_gender : '',
            'date_of_birth' => $d && $d->user_dob ? (string) $d->user_dob : null,
            'marital_status' => $d && $d->marital_status !== null ? (string) $d->marital_status : '',
            'email' => $l ? (string) ($l->email ?? '') : '',
            'mobile' => $l ? (string) ($l->user_mobile ?? '') : '',
            'status' => (int) $row->status,
            'user_type' => (int) $row->user_type,
            'user_type_label' => 'Executive (Super User)',
            'branch_count' => $branchCount,
            'updated_dtm' => $row->updated_dtm ?? null,
            'created_dtm' => $row->created_dtm ?? null,
            'avatar_url' => SuperUserAvatarStorageService::publicUrl($uid),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, scalar|null>
     */
    private function detailExtrasFromValidatedPayload(array $payload): array
    {
        $out = [];

        if (array_key_exists('gender', $payload) && $payload['gender'] !== null && $payload['gender'] !== '') {
            $out['user_gender'] = (string) $payload['gender'];
        }

        if (array_key_exists('date_of_birth', $payload) && $payload['date_of_birth'] !== null && $payload['date_of_birth'] !== '') {
            $out['user_dob'] = $payload['date_of_birth'];
        }

        if (array_key_exists('marital_status', $payload) && $payload['marital_status'] !== null && $payload['marital_status'] !== '') {
            $out['marital_status'] = (string) $payload['marital_status'];
        }

        return $out;
    }

    /**
     * @param  array<string, scalar|null>  $extra
     */
    private function mergeDetailExtras(?UserDetail $detail, array $extra): void
    {
        if ($detail === null || $extra === []) {
            return;
        }

        foreach ($extra as $key => $value) {
            if ($value !== null && $value !== '') {
                $detail->{$key} = $value;
            }
        }
        $detail->save();
    }

    private function validateNewUser(string $mobile, string $email): void
    {
        $v = Validator::make(
            ['user_mobile' => $mobile, 'email' => $email],
            [
                'user_mobile' => ['required', Rule::unique('user_login', 'user_mobile')],
                'email' => ['required', 'email', 'max:191'],
            ],
            [
                'user_mobile.unique' => 'This mobile number is already registered.',
            ]
        );
        if ($v->fails()) {
            throw new ValidationException($v);
        }
    }

    /**
     * @param  array<string, scalar|null>  $detailExtra
     */
    private function createNewUserWithMapping(
        string $firstName,
        string $lastName,
        string $mobile,
        string $email,
        string $plainPin,
        int $companyId,
        array $detailExtra = []
    ): int {
        return DB::transaction(function () use ($firstName, $lastName, $mobile, $email, $plainPin, $companyId, $detailExtra) {
            $row = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'user_status' => 1,
                'created_company_id' => $companyId,
            ];
            foreach ($detailExtra as $k => $v) {
                if ($v !== null && $v !== '') {
                    $row[$k] = $v;
                }
            }

            $detail = UserDetail::query()->create($row);

            $userId = (int) ($detail->getKey() ?: $detail->user_id ?: 0);
            if ($userId < 1) {
                $detail->refresh();
                $userId = (int) ($detail->getKey() ?: $detail->user_id ?: 0);
            }
            if ($userId < 1) {
                throw new \RuntimeException('Failed to resolve user_id after creating user detail.');
            }

            UserLogin::query()->create([
                'user_id' => $userId,
                'user_mobile' => $mobile,
                'email' => $email,
                'user_pin' => Hash::make($plainPin),
            ]);

            UserCompanies::query()->create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'user_type' => self::USER_TYPE_EXECUTIVE,
                'status' => 1,
                'creator_id' => $userId,
            ]);

            return $userId;
        });
    }

    private function sendExecutiveCredentialsMail(
        string $displayName,
        string $companyName,
        string $mobile,
        string $toEmail,
        ?string $plainPin,
        ?string $subjectLine = null
    ): void {
        try {
            PlatformMailConfigurator::apply();
            $mailManager = app('mail.manager');
            if (method_exists($mailManager, 'purge')) {
                $mailManager->purge();
            }
            Mail::to($toEmail)->send(new SuperUserWelcomeMail($displayName, $companyName, $mobile, $plainPin, $subjectLine));
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'email' => ['Unable to send email. Check platform mail settings and logs.'],
            ]);
        }
    }

    private function displayName(string $first, string $last): string
    {
        $last = trim($last);

        return $last !== '' ? trim($first.' '.$last) : trim($first);
    }

    private function normalizeMobile(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }
}
