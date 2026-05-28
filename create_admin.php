<?php
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Config::set('database.default', 'mysql');
Config::set('database.connections.mysql.host', 'mysql-semi-semi-project.l.aivencloud.com');
Config::set('database.connections.mysql.port', '20076');
Config::set('database.connections.mysql.database', 'sigma');
Config::set('database.connections.mysql.username', 'avnadmin');
Config::set('database.connections.mysql.password', env('DB_PASSWORD', 'your_password_here'));

// Purge connection to force reconnect with new config
DB::purge('mysql');
DB::reconnect('mysql');

$user = User::updateOrCreate(
    ['email' => 'riodwibinafitriono2@gmail.com'],
    [
        'name' => 'admin',
        'password' => Hash::make('12345678'),
        'role' => 'admin' // role was added to users table earlier
    ]
);

echo "Akun admin berhasil dibuat di database Vercel (Aiven Cloud)!";
