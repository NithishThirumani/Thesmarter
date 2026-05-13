<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\OrderTypesTableSeeder;
use Database\Seeders\AdminAuthSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UserSeeder::class);
        $this->call(RolesV2Seeder::class);
        $this->call(OrderTypesTableSeeder::class);
        $this->call(AdminAuthSeeder::class);
        // Admin auth: default admin + manish.gupta@bizwy.com (PIN 1234) when missing — see AdminAuthSeeder
        // Optional: provincial retail blueprint (Canada) — php artisan db:seed --class=CanadaTaxTemplatesSeeder
        // $this->call(CanadaTaxTemplatesSeeder::class);
    }
}
