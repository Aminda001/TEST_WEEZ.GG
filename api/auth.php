<?php
declare(strict_types=1);

require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/security.php';
require __DIR__ . '/../lib/csrf.php';
require __DIR__ . '/../lib/response.php';

start_secure_session();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'csrf') {
  json_out(['ok'=>true,'csrf'=>csrf_token()]);
  exit;
}

if ($method === 'GET' && $action === 'me') {
  json_out(['ok'=>true,'user'=>($_SESSION['user'] ?? null)]);
  exit;
}

if ($method === 'POST' && $action === 'login') {
  rate_limit('login', 8, 60);
  $raw = json_decode(file_get_contents('php://input'), true) ?? [];
  $username = trim((string)($raw['username'] ?? ''));
  $password = (string)($raw['password'] ?? '');

  if ($username === '' || $password === '') {
    json_out(['ok'=>false,'error'=>'Missing credentials'], 400);
    exit;
  }

  $pdo = db();
  $st = $pdo->prepare("SELECT id, username, role, password_hash FROM users WHERE LOWER(username)=LOWER(?) LIMIT 1");
  $st->execute([$username]);
  $u = $st->fetch();

  if (!$u || !password_verify($password, $u['password_hash'])) {
    json_out(['ok'=>false,'error'=>'Invalid username or password'], 401);
    exit;
  }

  session_regenerate_id(true);
  $_SESSION['user'] = ['id'=>(int)$u['id'], 'username'=>$u['username'], 'role'=>$u['role']];
  csrf_token();

  json_out(['ok'=>true,'user'=>$_SESSION['user']]);
  exit;
}

if ($method === 'POST' && $action === 'logout') {
  require_login();
  require_csrf();

  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
  json_out(['ok'=>true]);
  exit;
}

json_out(['ok'=>false,'error'=>'Not found'], 404);
