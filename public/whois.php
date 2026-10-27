<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\{Auth, Database, Request, Response};

Request::boot(['GET']);
Auth::requireAuth();

$limit = filter_var($_GET['limit'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
if ($limit === false) {
    Response::error('"limit" must be an integer between 1 and 100', 422);
}

$sql = 'SELECT log_id, domain_name, title, ip, domain_creation_date FROM whois_log';
$params = [];

$domain = $_GET['domain_name'] ?? null;
if ($domain !== null && $domain !== '') {
    $sql .= ' WHERE domain_name = ?';
    $params[] = (string)$domain;
}

// LIMIT عدد صحیح اعتبارسنجی‌شده است؛ ORDER BY ثابت (نه از ورودی کاربر)
$sql .= ' ORDER BY log_id DESC LIMIT ' . $limit;

$stmt = Database::connection()->prepare($sql);
$stmt->execute($params);

Response::json(['data' => $stmt->fetchAll()]);
