<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

if (file_exists(__DIR__.'/../.env')) {
    (Dotenv\Dotenv::createImmutable(__DIR__.'/..'))->safeLoad();
}

$db = $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE');

if (is_string($db) && $db !== '' && ! str_ends_with($db, '_test')) {
    $name = "{$db}_test";
    putenv("DB_DATABASE={$name}");
    $_ENV['DB_DATABASE'] = $name;
    $_SERVER['DB_DATABASE'] = $name;
}
