<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('order_types')->insert([
            [
                'name' => 'Standard',
                'description' => 'Regular customer orders',
            ],
            [
                'name' => 'Wholesale',
                'description' => 'Bulk orders with negotiated pricing',
            ],
            [
                'name' => 'Pre-order',
                'description' => 'Orders for upcoming products',
            ],
            [
                'name' => 'Return',
                'description' => 'Product returns/RMAs',
            ],
        ]);
    }
}
