<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\{Database, Request, Response};

Request::boot(['POST']);

$data = Request::jsonBody();
$username = trim((string)($data['user'] ?? ''));
$password = (string)($data['pass'] ?? '');
$firstName = trim((string)($data['first_name'] ?? ''));
$family = trim((string)($data['family'] ?? ''));
$email = trim((string)($data['email'] ?? ''));

if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    Response::error('"user" must be 3-50 chars: letters, digits, _ . -', 422);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    Response::error('"pass" must be between 8 and 72 characters', 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('"email" is not valid', 422);
}

$pdo = Database::connection();
$exists = $pdo->prepare('SELECT 1 FROM `user` WHERE user = ? LIMIT 1');
$exists->execute([$username]);
if ($exists->fetchColumn()) {
    Response::error('User already exists', 409);
}

$pdo->prepare('INSERT INTO `user` (user, pass, first_name, family, email) VALUES (?, ?, ?, ?, ?)')
    ->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        $firstName ?: null,
        $family ?: null,
        $email ?: null,
    ]);

Response::json(['message' => 'User created', 'id' => (int)$pdo->lastInsertId()], 201);
