<?php
declare(strict_types=1);

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function require_csrf(): void {
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!$token || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    json_out(['ok'=>false,'error'=>'CSRF failed'], 419);
    exit;
  }
}
