<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Order;

echo "--- ROLES ---\n";
foreach (User::select('role')->distinct()->get() as $u) {
    echo $u->role . "\n";
}

echo "\n--- STATUSES ---\n";
foreach (Order::select('status')->distinct()->get() as $o) {
    echo $o->status . "\n";
}
