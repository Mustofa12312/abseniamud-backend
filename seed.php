<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'budi@iaimu.ac.id')->first();

App\Models\Lecturer::firstOrCreate(
    ['user_id' => $user->id],
    [
        'nidn' => '1234567891',
        'nip' => '198001012005011002',
        'phone' => '081234567891'
    ]
);

echo "Seeded successfully\n";
