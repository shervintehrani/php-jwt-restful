<?php
declare(strict_types=1);

namespace App;

use Firebase\JWT\ExpiredException;

final class Auth
{
    /** میدلور: توکن را بررسی می‌کند و payload را برمی‌گرداند، وگرنه 401 */
    public static function requireAuth(): object
    {
        $token = Request::bearerToken();
        if ($token === null) {
            Response::error('Authorization token required', 401);
        }
        try {
            return Jwt::verify($token);
        } catch (ExpiredException) {
            Response::error('Token expired', 401);
        } catch (ConfigException $e) {
            // خطای پیکربندی (مثلاً JWT_SECRET) نباید به کاربر لو برود
            error_log('JWT config error: ' . $e->getMessage());
            Response::error('Server configuration error', 500);
        } catch (\Throwable $e) {
            error_log('JWT rejected: ' . $e->getMessage());
            Response::error('Invalid token', 401);
        }
    }
}
