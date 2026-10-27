<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Config::get('DB_HOST', 'localhost'),
                Config::get('DB_PORT', '3306'),
                Config::get('DB_NAME', 'api')
            );
            self::$pdo = new PDO($dsn, Config::require('DB_USER'), Config::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }

    /** برای تست‌ها: تزریق اتصال دلخواه (مثلاً SQLite) */
    public static function setConnection(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
