<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'benjambuthia@gmail.com';

$userRows = DB::table('users')
    ->where('email', $email)
    ->get(['id', 'email', 'role', 'name', 'created_at']);

$adminRows = DB::table('admins')
    ->where('email', $email)
    ->get(['id', 'email', 'name', 'created_at']);

echo "Users matched: " . $userRows->count() . PHP_EOL;
echo json_encode($userRows, JSON_PRETTY_PRINT) . PHP_EOL;
echo "Admins matched: " . $adminRows->count() . PHP_EOL;
echo json_encode($adminRows, JSON_PRETTY_PRINT) . PHP_EOL;

if ($userRows->isEmpty()) {
    echo "Nothing to delete in users table." . PHP_EOL;
    return;
}

if ($userRows->contains(fn ($user) => strtolower((string) $user->role) === 'admin')) {
    echo "Refusing to delete because a matching users-table row has role=admin." . PHP_EOL;
    return;
}

$userIds = $userRows->pluck('id')->all();

DB::transaction(function () use ($email, $userIds) {
    if (Schema::hasTable('personal_access_tokens')) {
        $deletedTokens = DB::table('personal_access_tokens')
            ->where('tokenable_type', \App\Models\User::class)
            ->whereIn('tokenable_id', $userIds)
            ->delete();
        echo "Deleted personal_access_tokens: {$deletedTokens}" . PHP_EOL;
    }

    if (Schema::hasTable('user_devices')) {
        $deletedDevices = DB::table('user_devices')
            ->whereIn('user_id', $userIds)
            ->delete();
        echo "Deleted user_devices: {$deletedDevices}" . PHP_EOL;
    }

    $deletedUsers = DB::table('users')
        ->where('email', $email)
        ->whereIn('id', $userIds)
        ->delete();

    echo "Deleted users: {$deletedUsers}" . PHP_EOL;
});
