<?php
declare(strict_types=1);

require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/security.php';
require __DIR__ . '/../lib/csrf.php';
require __DIR__ . '/../lib/response.php';

start_secure_session();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = require_login();
$pdo = db();

if ($method === 'GET') {
  // Customers & admins can see items (no cost shown to customers in API response)
  $items = $pdo->query("SELECT id, code, name, price, quantity, details, emoji, category FROM items ORDER BY id DESC")->fetchAll();
  json_out(['ok'=>true,'items'=>$items]);
  exit;
}

if ($method === 'POST') {
  require_admin();
  require_csrf();
  $raw = json_decode(file_get_contents('php://input'), true) ?? [];

  $code = trim((string)($raw['code'] ?? ''));
  $name = trim((string)($raw['name'] ?? ''));
  $cost = max(0.0, (float)($raw['cost'] ?? 0));
  $price = max(0.0, (float)($raw['price'] ?? 0));
  $qty = max(0, (int)($raw['quantity'] ?? 0));
  $details = trim((string)($raw['details'] ?? ''));
  $emoji = trim((string)($raw['emoji'] ?? '📦'));
  $category = trim((string)($raw['category'] ?? 'General'));

  if ($code === '' || $name === '') {
    json_out(['ok'=>false,'error'=>'Code and name required'], 400);
    exit;
  }

  $st = $pdo->prepare("INSERT INTO items(code,name,cost,price,quantity,details,emoji,category) VALUES(?,?,?,?,?,?,?,?)");
  $st->execute([$code,$name,$cost,$price,$qty,$details,$emoji,$category]);
  json_out(['ok'=>true]);
  exit;
}

if ($method === 'PUT') {
  require_admin();
  require_csrf();
  $raw = json_decode(file_get_contents('php://input'), true) ?? [];
  $id = (int)($raw['id'] ?? 0);
  if ($id <= 0) { json_out(['ok'=>false,'error'=>'Invalid id'], 400); exit; }

  $fields = ['code','name','cost','price','quantity','details','emoji','category'];
  $set = [];
  $vals = [];
  foreach ($fields as $f) {
    if (array_key_exists($f, $raw)) {
      $set[] = "$f=?";
      $vals[] = $raw[$f];
    }
  }
  if (!$set) { json_out(['ok'=>false,'error'=>'No fields'], 400); exit; }

  $vals[] = $id;
  $sql = "UPDATE items SET ".implode(',', $set)." WHERE id=?";
  $st = $pdo->prepare($sql);
  $st->execute($vals);

  json_out(['ok'=>true]);
  exit;
}

if ($method === 'DELETE') {
  require_admin();
  require_csrf();
  parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
  $id = (int)($q['id'] ?? 0);
  if ($id <= 0) { json_out(['ok'=>false,'error'=>'Invalid id'], 400); exit; }

  $st = $pdo->prepare("DELETE FROM items WHERE id=?");
  $st->execute([$id]);
  json_out(['ok'=>true]);
  exit;
}

json_out(['ok'=>false,'error'=>'Method not allowed'], 405);
