<?php

namespace Tests\Feature;

use App\Modules\AdminAuth\Models\AdminOtp;
use App\Modules\AdminAuth\Models\AdminRefreshToken;
use App\Modules\AdminAuth\Models\AdminUser;
use App\Modules\AdminAuth\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function bearerFor(AdminUser $admin): string
    {
        return app(JwtService::class)->issueAccessToken($admin);
    }

    public function test_super_admin_can_list_platform_admins(): void
    {
        $super = AdminUser::query()->create([
            'email' => 'super@test.com',
            'name' => 'Super',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->bearerFor($super),
            'Accept' => 'application/json',
        ])->getJson('/api/admin/platform-admins');

        $response->assertStatus(200)->assertJson(['success' => true])->assertJsonStructure(['data', 'meta']);
    }

    public function test_non_super_admin_is_blocked_from_management_api(): void
    {
        $regular = AdminUser::query()->create([
            'email' => 'regular@test.com',
            'name' => 'Regular',
            'role' => 'admin',
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->bearerFor($regular),
            'Accept' => 'application/json',
        ])->getJson('/api/admin/platform-admins');

        $response->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_super_admin_can_create_super_admin(): void
    {
        Mail::fake();

        $super = AdminUser::query()->create([
            'email' => 'super2@test.com',
            'name' => 'Super',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->bearerFor($super),
            'Accept' => 'application/json',
        ])->postJson('/api/admin/platform-admins', [
            'name' => 'Another Super',
            'email' => 'another@test.com',
            'phone_number' => null,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('admin_users', [
            'email' => 'another@test.com',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_duplicate_email_on_create_returns_422(): void
    {
        Mail::fake();

        $super = AdminUser::query()->create([
            'email' => 'super3@test.com',
            'name' => 'Super',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        AdminUser::query()->create([
            'email' => 'dup@test.com',
            'name' => 'Existing',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'pin_hash' => Hash::make('5678'),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->bearerFor($super),
            'Accept' => 'application/json',
        ])->postJson('/api/admin/platform-admins', [
            'name' => 'Dup Try',
            'email' => 'dup@test.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_super_admin_can_delete_another_super_admin(): void
    {
        $super = AdminUser::query()->create([
            'email' => 'del_actor@test.com',
            'name' => 'Actor',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $target = AdminUser::query()->create([
            'email' => 'del_target@test.com',
            'name' => 'Target',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'pin_hash' => Hash::make('5678'),
            'is_active' => true,
        ]);

        AdminRefreshToken::query()->create([
            'admin_id' => $target->id,
            'token_hash' => hash('sha256', 'tok'),
            'expires_at' => now()->addDay(),
        ]);
        AdminOtp::query()->create([
            'admin_id' => $target->id,
            'otp_hash' => hash('sha256', 'otp'),
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
            'attempt_count' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->bearerFor($super),
            'Accept' => 'application/json',
        ])->deleteJson('/api/admin/platform-admins/'.$target->id);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseMissing('admin_users', ['id' => $target->id]);
        $this->assertDatabaseMissing('admin_refresh_tokens', ['admin_id' => $target->id]);
        $this->assertDatabaseMissing('admin_otp', ['admin_id' => $target->id]);
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $super = AdminUser::query()->create([
            'email' => 'self@test.com',
            'name' => 'Self',
            'role' => AdminUser::ROLE_SUPER_ADMIN,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->bearerFor($super),
            'Accept' => 'application/json',
        ])->deleteJson('/api/admin/platform-admins/'.$super->id);

        $response->assertStatus(422)->assertJson(['success' => false]);

        $this->assertDatabaseHas('admin_users', ['id' => $super->id]);
    }

    public function test_guest_receives_401(): void
    {
        $this->getJson('/api/admin/platform-admins')->assertStatus(401);
    }
}
