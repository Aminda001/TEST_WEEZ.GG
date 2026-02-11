<?php
declare(strict_types=1);

function start_secure_session(): void {
  $cfg = require __DIR__ . '/config.php';
  $s = $cfg['session'];

  session_name($s['name']);
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (bool)$s['secure'],
    'httponly' => (bool)$s['httponly'],
    'samesite' => (string)$s['samesite'],
  ]);

  if (session_status() !== PHP_SESSION_ACTIVE) session_start();

  // Regenerate occasionally
  if (!isset($_SESSION['__regen_at'])) {
    session_regenerate_id(true);
    $_SESSION['__regen_at'] = time();
  } elseif (time() - (int)$_SESSION['__regen_at'] > 900) { // 15 min
    session_regenerate_id(true);
    $_SESSION['__regen_at'] = time();
  }
}

function require_login(): array {
  if (empty($_SESSION['user'])) {
    json_out(['ok'=>false,'error'=>'Unauthorized'], 401);
    exit;
  }
  return $_SESSION['user'];
}

function require_admin(): array {
  $u = require_login();
  if (($u['role'] ?? '') !== 'admin') {
    json_out(['ok'=>false,'error'=>'Forbidden'], 403);
    exit;
  }
  return $u;
}

function client_ip(): string {
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function rate_limit(string $bucket, int $max, int $window_seconds): void {
  // Session-based lightweight RL (good for shared hosting). For stronger RL, use Redis.
  $ip = client_ip();
  $k = "rl:$bucket:$ip";
  $now = time();
  if (!isset($_SESSION[$k])) {
    $_SESSION[$k] = ['count'=>0,'reset'=>$now+$window_seconds];
  }
  if ($now > (int)$_SESSION[$k]['reset']) {
    $_SESSION[$k] = ['count'=>0,'reset'=>$now+$window_seconds];
  }
  $_SESSION[$k]['count'] = (int)$_SESSION[$k]['count'] + 1;

  if ((int)$_SESSION[$k]['count'] > $max) {
    json_out(['ok'=>false,'error'=>'Too many requests'], 429);
    exit;
  }
}
