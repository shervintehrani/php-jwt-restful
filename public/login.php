<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\{Database, Jwt, Request, Response};

Request::boot(['POST']);

$data = Request::jsonBody();
$username = trim((string)($data['user'] ?? ''));
$password = (string)($data['pass'] ?? '');

if ($username === '' || $password === '') {
    Response::error('Fields "user" and "pass" are required', 400);
}

$stmt = Database::connection()->prepare('SELECT id, user, pass FROM `user` WHERE user = ? LIMIT 1');
$stmt->execute([$username]);
$row = $stmt->fetch();

// برای جلوگیری از تفاوت زمانی (user enumeration) همیشه یک verify انجام می‌شود
$hash = $row['pass'] ?? password_hash('dummy', PASSWORD_DEFAULT);
$ok = password_verify($password, $hash) && $row !== false;

if (!$ok) {
    Response::json(['authorized' => false, 'error' => 'Invalid credentials'], 401);
}

if (password_needs_rehash($row['pass'], PASSWORD_DEFAULT)) {
    Database::connection()
        ->prepare('UPDATE `user` SET pass = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
}

Response::json([
    'authorized' => true,
    'token' => Jwt::issue($row['id'], $row['user']),
    'token_type' => 'Bearer',
    'expires_in' => (int)App\Config::get('JWT_TTL', '1200'),
]);
