<?php
declare(strict_types=1);

namespace App;

/**
 * خواندن تنظیمات از متغیرهای محیطی (و در صورت وجود، فایل .env).
 */
final class Config
{
    private static bool $loaded = false;

    public static function get(string $key, ?string $default = null): ?string
    {
        self::loadDotEnv();
        $value = $_ENV[$key] ?? getenv($key);
        return ($value === false || $value === null || $value === '') ? $default : (string)$value;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null) {
            throw new ConfigException("Missing required config: $key");
        }
        return $value;
    }

    private static function loadDotEnv(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        $file = dirname(__DIR__) . '/.env';
        if (!is_readable($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\"'");
            if (getenv($k) === false && !isset($_ENV[$k])) {
                $_ENV[$k] = $v;
            }
        }
    }
}
