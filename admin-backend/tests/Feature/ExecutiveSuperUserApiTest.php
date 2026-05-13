<?php

namespace Tests\Feature;

use App\Modules\AdminAuth\Models\AdminUser;
use App\Modules\AdminAuth\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExecutiveSuperUserApiTest extends TestCase
{
    use RefreshDatabase;

    private function bearerFor(AdminUser $admin): string
    {
        return app(JwtService::class)->issueAccessToken($admin);
    }

    public function test_modules_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/companies/1/super-users/modules')->assertStatus(401);
    }

    public function test_modules_returns_404_when_company_not_found(): void
    {
        $admin = AdminUser::query()->create([
            'email' => 'exec-modules@test.com',
            'name' => 'Admin',
            'role' => 'admin',
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->bearerFor($admin),
            'Accept' => 'application/json',
        ])->getJson('/api/companies/99999/super-users/modules')
            ->assertStatus(404);
    }

}
