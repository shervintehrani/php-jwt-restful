<?php
declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Dependencies not installed. Run: composer install']);
    exit;
}
require_once $autoload;

ini_set('display_errors', '0');

// خطاهای پیش‌بینی‌نشده را JSON و بدون جزئیات داخلی برگردان
set_exception_handler(static function (\Throwable $e): void {
    error_log((string)$e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['error' => 'Internal server error']);
});
