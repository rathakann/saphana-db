<?php

namespace App\Providers;

use App\Database\Hana\HanaConnection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use PDO;

class HanaServiceProvider extends ServiceProvider
{
    public function boot(DatabaseManager $database): void
    {
        $database->extend('saphana', function ($config, $name) {
            $pdo = new PDO(
                $config['dsn'],
                $config['username'] ?? null,
                $config['password'] ?? null,
                $config['options'] ?? []
            );

            return new HanaConnection(
                $pdo,
                $config['database'] ?? '',
                $config['prefix'] ?? '',
                $config
            );
        });
    }
}
