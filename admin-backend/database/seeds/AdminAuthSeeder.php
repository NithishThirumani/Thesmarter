<?php

namespace Database\Seeders;

use App\Modules\AdminAuth\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAuthSeeder extends Seeder
{
    /**
     * Seeds admin users when missing.
     * Run: php artisan db:seed --class=AdminAuthSeeder
     */
    public function run()
    {
        $accounts = [
            [
                'email' => strtolower(trim(env('ADMIN_SEED_EMAIL', 'admin@example.com'))),
                'pin' => env('ADMIN_SEED_PIN', '1234'),
            ],
            [
                'email' => 'manish.gupta@bizwy.com',
                'pin' => '1234',
            ],
        ];

        foreach ($accounts as $row) {
            if (AdminUser::where('email', $row['email'])->exists()) {
                continue;
            }

            AdminUser::create([
                'email' => $row['email'],
                'pin_hash' => Hash::make($row['pin']),
                'is_active' => true,
            ]);
        }
    }
}
