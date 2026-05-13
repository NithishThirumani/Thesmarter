<?php

namespace App\Modules\AdminPlatformAdmin\Services;

use App\Mail\PlatformAdminInvitationMail;
use App\Mail\PlatformAdminPinResetMail;
use App\Modules\AdminAuth\Models\AdminOtp;
use App\Modules\AdminAuth\Models\AdminRefreshToken;
use App\Modules\AdminAuth\Models\AdminUser;
use App\Support\Mail\PlatformMailConfigurator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Manage {@see AdminUser::ROLE_SUPER_ADMIN} portal accounts (all created admins stay super_admin; emails use platform mail settings).
 */
class PlatformAdminService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = AdminUser::query()->where('role', AdminUser::ROLE_SUPER_ADMIN);

        if (!empty($filters['search'])) {
            $t = '%'.$filters['search'].'%';
            $q->where(function ($w) use ($t) {
                $w->where('name', 'like', $t)
                    ->orWhere('email', 'like', $t)
                    ->orWhere('phone_number', 'like', $t);
            });
        }

        $status = $filters['status'] ?? null;
        if ($status !== null && $status !== '' && $status !== 'all') {
            if ($status === 'active' || $status === 1 || $status === '1') {
                $q->where('is_active', true);
            } elseif ($status === 'inactive' || $status === 0 || $status === '0') {
                $q->where('is_active', false);
            }
        }

        $sort = is_string($filters['sort'] ?? null) ? strtolower(trim((string) $filters['sort'])) : '';
        switch ($sort) {
            case 'created_asc':
                $q->orderBy('created_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'updated_desc':
                $q->orderBy('updated_at', 'desc')->orderBy('id', 'desc');
                break;
            case 'updated_asc':
                $q->orderBy('updated_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'name_asc':
                $q->orderBy('name', 'asc')->orderBy('id', 'desc');
                break;
            case 'name_desc':
                $q->orderBy('name', 'desc')->orderBy('id', 'desc');
                break;
            case 'created_desc':
            default:
                $q->orderBy('created_at', 'desc')->orderBy('id', 'desc');
                break;
        }

        return $q->paginate($perPage);
    }

    public function findManaged(string $id): AdminUser
    {
        return AdminUser::query()
            ->where('id', $id)
            ->where('role', AdminUser::ROLE_SUPER_ADMIN)
            ->firstOrFail();
    }

    /**
     * @param  array{name: string, email: string, phone_number?: string|null}  $data
     */
    public function create(array $data, AdminUser $actor): AdminUser
    {
        $email = strtolower(trim($data['email']));
        if (AdminUser::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['This email is already registered.']]);
        }

        $plainPin = $this->generateFourDigitPin();

        return DB::transaction(function () use ($data, $actor, $email, $plainPin) {
            $model = AdminUser::create([
                'email' => $email,
                'name' => trim($data['name']),
                'phone_number' => isset($data['phone_number']) && trim((string) $data['phone_number']) !== ''
                    ? trim((string) $data['phone_number'])
                    : null,
                'role' => AdminUser::ROLE_SUPER_ADMIN,
                'pin_hash' => Hash::make($plainPin),
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->dispatchInvitationMailOrFail($model, $plainPin);

            return $model->fresh();
        });
    }

    /**
     * @param  array{name?: string, email?: string, phone_number?: string|null, is_active?: bool}  $data
     */
    public function update(string $id, array $data, AdminUser $actor): AdminUser
    {
        $admin = $this->findManaged($id);

        if (isset($data['email'])) {
            $email = strtolower(trim($data['email']));
            if ($email !== $admin->email && AdminUser::where('email', $email)->where('id', '!=', $admin->id)->exists()) {
                throw ValidationException::withMessages(['email' => ['This email is already registered.']]);
            }
            $admin->email = $email;
        }
        if (array_key_exists('name', $data)) {
            $admin->name = trim((string) $data['name']);
        }
        if (array_key_exists('phone_number', $data)) {
            $v = trim((string) ($data['phone_number'] ?? ''));
            $admin->phone_number = $v !== '' ? $v : null;
        }
        if (array_key_exists('is_active', $data)) {
            $admin->is_active = (bool) $data['is_active'];
        }
        $admin->updated_by = $actor->id;
        $admin->save();

        return $admin->fresh();
    }

    public function setActive(string $id, bool $isActive, AdminUser $actor): AdminUser
    {
        $admin = $this->findManaged($id);
        $admin->is_active = $isActive;
        $admin->updated_by = $actor->id;
        $admin->save();

        if (!$isActive) {
            AdminRefreshToken::where('admin_id', $admin->id)->delete();
        }

        return $admin->fresh();
    }

    /**
     * @return array{admin: AdminUser, mail_sent: bool}
     */
    public function resetPin(string $id, AdminUser $actor): array
    {
        $admin = $this->findManaged($id);
        $plainPin = $this->generateFourDigitPin();

        DB::transaction(function () use ($admin, $plainPin, $actor) {
            $admin->pin_hash = Hash::make($plainPin);
            $admin->updated_by = $actor->id;
            $admin->save();
            AdminRefreshToken::where('admin_id', $admin->id)->delete();
        });

        $admin->refresh();
        $mailSent = $this->trySendPinResetMail($admin, $plainPin);

        return ['admin' => $admin, 'mail_sent' => $mailSent];
    }

    public function delete(string $id, AdminUser $actor): void
    {
        if ($actor->id === $id) {
            throw ValidationException::withMessages(['id' => ['You cannot delete your own account.']]);
        }

        $admin = $this->findManaged($id);

        DB::transaction(function () use ($admin) {
            AdminRefreshToken::where('admin_id', $admin->id)->delete();
            AdminOtp::where('admin_id', $admin->id)->delete();
            $admin->delete();
        });
    }

    public function rowToArray(AdminUser $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'phone_number' => $admin->phone_number,
            'status' => $admin->is_active ? 'active' : 'inactive',
            'is_active' => $admin->is_active,
            'created_at' => $admin->created_at?->toIso8601String(),
            'updated_at' => $admin->updated_at?->toIso8601String(),
        ];
    }

    private function generateFourDigitPin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function loginPortalUrl(): string
    {
        $base = rtrim((string) config('admin-auth.admin_portal_url', config('app.url')), '/');

        return $base.'/login';
    }

    private function dispatchInvitationMailOrFail(AdminUser $admin, string $plainPin): void
    {
        try {
            PlatformMailConfigurator::apply();
            $mailManager = app('mail.manager');
            if (method_exists($mailManager, 'purge')) {
                $mailManager->purge();
            }
            $name = trim((string) $admin->name) !== '' ? $admin->name : $admin->email;
            Mail::to($admin->email)->send(new PlatformAdminInvitationMail(
                $name,
                $admin->email,
                $plainPin,
                $this->loginPortalUrl()
            ));
            Log::info('platform_admin.invitation_sent', ['admin_id' => $admin->id]);
        } catch (\Throwable $e) {
            Log::warning('platform_admin.invitation_mail_failed', [
                'admin_id' => $admin->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to send invitation email. Check platform mail settings.');
        }
    }

    private function trySendPinResetMail(AdminUser $admin, string $plainPin): bool
    {
        try {
            PlatformMailConfigurator::apply();
            $mailManager = app('mail.manager');
            if (method_exists($mailManager, 'purge')) {
                $mailManager->purge();
            }
            $name = trim((string) $admin->name) !== '' ? $admin->name : $admin->email;
            Mail::to($admin->email)->send(new PlatformAdminPinResetMail(
                $name,
                $admin->email,
                $plainPin,
                $this->loginPortalUrl()
            ));
            Log::info('platform_admin.pin_reset_sent', ['admin_id' => $admin->id]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('platform_admin.pin_reset_mail_failed', [
                'admin_id' => $admin->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}