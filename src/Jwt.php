<?php
declare(strict_types=1);

namespace App;

use Firebase\JWT\JWT as FirebaseJwt;
use Firebase\JWT\Key;

final class Jwt
{
    private const ALGORITHM = 'HS256';

    private static function secret(): string
    {
        $secret = Config::require('JWT_SECRET');
        if (strlen($secret) < 32) {
            throw new ConfigException('JWT_SECRET must be at least 32 characters');
        }
        return $secret;
    }

    public static function issue(int|string $userId, string $username): string
    {
        $now = time();
        $ttl = (int)Config::get('JWT_TTL', '1200');
        $issuer = Config::get('JWT_ISSUER', 'http://localhost');

        return FirebaseJwt::encode([
            'iss' => $issuer,
            'aud' => $issuer,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'sub' => (string)$userId,
            'user' => $username,
        ], self::secret(), self::ALGORITHM);
    }

    /**
     * @return object payload
     * @throws \Throwable در صورت نامعتبر/منقضی بودن توکن
     */
    public static function verify(string $token): object
    {
        FirebaseJwt::$leeway = 5;
        $payload = FirebaseJwt::decode($token, new Key(self::secret(), self::ALGORITHM));

        $issuer = Config::get('JWT_ISSUER', 'http://localhost');
        if (($payload->iss ?? null) !== $issuer || ($payload->aud ?? null) !== $issuer) {
            throw new \UnexpectedValueException('Invalid issuer/audience');
        }
        return $payload;
    }
}
