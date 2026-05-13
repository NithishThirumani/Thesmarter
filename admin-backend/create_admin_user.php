<?php
/**
 * One-off script to create the default admin user. Run from project root:
 * php create_admin_user.php
 * Or: docker exec thesmartr-backend php /var/www/create_admin_user.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Modules\AdminAuth\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

$email = env('ADMIN_SEED_EMAIL', 'admin@example.com');
$pin = env('ADMIN_SEED_PIN', '1234');

if (AdminUser::where('email', $email)->exists()) {
    echo "Admin already exists: $email\n";
    exit(0);
}

AdminUser::create([
    'email' => $email,
    'pin_hash' => Hash::make($pin),
    'is_active' => true,
]);

echo "Admin created: $email / PIN $pin\n";
