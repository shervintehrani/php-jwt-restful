<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\{Auth, Request, Response};

Request::boot(['GET']);

$payload = Auth::requireAuth();

Response::json([
    'id' => $payload->sub,
    'user' => $payload->user,
    'expires_at' => $payload->exp,
]);
