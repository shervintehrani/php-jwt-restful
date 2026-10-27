<?php
declare(strict_types=1);

namespace App;

final class Request
{
    /** اعمال CORS (اختیاری) و پاسخ به OPTIONS، و محدودکردن متد */
    public static function boot(array $allowedMethods): void
    {
        $origin = Config::get('CORS_ALLOW_ORIGIN');
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods) . ', OPTIONS');
            header('Vary: Origin');
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        if (!in_array($method, $allowedMethods, true)) {
            header('Allow: ' . implode(', ', $allowedMethods));
            Response::error('Method not allowed', 405);
        }
    }

    /** بدنه‌ی JSON درخواست؛ در صورت نامعتبر بودن، 400 */
    public static function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw === false ? '' : $raw, true);
        if (!is_array($data)) {
            Response::error('Invalid or missing JSON body', 400);
        }
        return $data;
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? null) : null);

        if ($header && preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }
}
