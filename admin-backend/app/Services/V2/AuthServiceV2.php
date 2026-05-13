<?php

namespace App\Services\V2;

use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use App\UserCompanyRoleV2;
use App\UserCredentialV2;
use App\UserLogin;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthServiceV2
{
    protected $authRepository;
    protected $userRepository;
    protected $migrationService;
    protected $userService;

    public function __construct(
        AuthRepositoryInterface $authRepository,
        UserRepositoryInterface $userRepository,
        UserV2MigrationService $migrationService,
        UserService $userService
    ) {
        $this->authRepository = $authRepository;
        $this->userRepository = $userRepository;
        $this->migrationService = $migrationService;
        $this->userService = $userService;
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $login, string $pin, ?int $companyId = null): array
    {
        $parsed = $this->parseIdentifier($login);
        $type = $parsed['type'];
        $value = $parsed['value'];

        $userId = $this->resolveUserIdAfterCredentialCheck($type, $value, $pin);
        $contexts = $this->buildContexts($userId);
        if ($contexts->isEmpty()) {
            throw new Exception('No active company access for this user.');
        }

        if ($contexts->count() > 1 && $companyId === null) {
            return [
                'needs_company_selection' => true,
                'contexts' => $contexts->values()->all(),
            ];
        }

        if ($contexts->count() === 1) {
            $companyId = (int) $contexts->first()['company_id'];
        }

        if ($companyId === null) {
            throw new Exception('company_id is required when the user has multiple companies.');
        }

        $selected = $contexts->first(function ($c) use ($companyId) {
            return (int) $c['company_id'] === (int) $companyId;
        });
        if (!$selected) {
            throw new Exception('Invalid company for this user.');
        }

        return $this->issueTokenResponse(
            (int) $userId,
            (int) $selected['company_id'],
            (int) $selected['role_id']
        );
    }

    private function parseIdentifier(string $raw): array
    {
        $t = trim($raw);
        if (strpos($t, '@') !== false) {
            return [
                'type' => 'email',
                'value' => strtolower($t),
            ];
        }
        $digits = preg_replace('/\D+/', '', $t);

        return [
            'type' => 'mobile',
            'value' => $digits,
        ];
    }

    private function resolveUserIdAfterCredentialCheck(string $type, string $value, string $pin): int
    {
        $candidates = UserCredentialV2::query()
            ->where('login_type', $type)
            ->where('login_value', $value)
            ->get();

        if ($candidates->isNotEmpty()) {
            foreach ($candidates as $cred) {
                if (Hash::check($pin, $cred->password_hash)) {
                    return (int) $cred->user_id;
                }
            }
            throw new Exception('Invalid credentials.');
        }

        return $this->authenticateV1AndMigrate($type, $value, $pin);
    }

    private function authenticateV1AndMigrate(string $type, string $value, string $pin): int
    {
        $userLogin = $type === 'email'
            ? $this->userRepository->findUserByEmail($value)
            : $this->userRepository->findUserByMobile($value);
        if (!$userLogin) {
            throw new Exception('Invalid credentials.');
        }
        if (!$this->authRepository->verifyPin($userLogin, $pin)) {
            throw new Exception('Invalid credentials.');
        }
        if (!$this->userService->checkUserAccess($userLogin->user_id)) {
            throw new Exception('Access denied; contact your administrator.');
        }
        $this->migrationService->syncFromV1($userLogin);

        return (int) $userLogin->user_id;
    }

    private function buildContexts(int $userId): Collection
    {
        return UserCompanyRoleV2::query()
            ->where('user_id', $userId)
            ->where('status', 1)
            ->with(['role', 'company'])
            ->get()
            ->map(function (UserCompanyRoleV2 $row) {
                return [
                    'company_id' => (int) $row->company_id,
                    'company_name' => $row->company
                        ? (string) $row->company->company_name
                        : null,
                    'role_id' => (int) $row->role_id,
                    'role_name' => $row->role
                        ? (string) $row->role->role_name
                        : null,
                ];
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function issueTokenResponse(int $userId, int $companyId, int $roleId): array
    {
        $user = UserLogin::query()->where('user_id', $userId)->firstOrFail();
        $mapping = UserCompanyRoleV2::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('role_id', $roleId)
            ->where('status', 1)
            ->first();
        if (!$mapping) {
            throw new Exception('Company role mapping not found or inactive.');
        }
        $token = JWTAuth::claims([
            'company_id' => $companyId,
            'role_id' => $roleId,
            'actor' => 'staff',
        ])->fromUser($user);
        $ttlMinutes = (int) config('jwt.ttl', 60);

        return [
            'needs_company_selection' => false,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttlMinutes * 60,
            'user_id' => $userId,
            'company_id' => $companyId,
            'role_id' => $roleId,
            'actor' => 'staff',
        ];
    }
}
