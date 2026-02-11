<?php
declare(strict_types=1);

require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/security.php';
require __DIR__ . '/../lib/csrf.php';
require __DIR__ . '/../lib/response.php';

start_secure_session();
$user = require_login();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  if ($user['role'] === 'admin') {
    $orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
  } else {
    $st = $pdo->prepare("SELECT * FROM orders WHERE customer_user_id=? ORDER BY created_at DESC");
    $st->execute([$user['id']]);
    $orders = $st->fetchAll();
  }
  json_out(['ok'=>true,'orders'=>$orders]);
  exit;
}

if ($method === 'POST') {
  require_csrf();
  if ($user['role'] !== 'customer') {
    json_out(['ok'=>false,'error'=>'Customers only'], 403);
    exit;
  }

  $raw = json_decode(file_get_contents('php://input'), true) ?? [];
  $itemId = (int)($raw['itemId'] ?? 0);
  $qty = max(1, (int)($raw['quantity'] ?? 1));
  $paymentMethod = trim((string)($raw['paymentMethod'] ?? 'Cash'));
  $offerCode = trim((string)($raw['offerCode'] ?? ''));

  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare("SELECT id, code, name, emoji, price, cost, quantity FROM items WHERE id=? FOR UPDATE");
    $st->execute([$itemId]);
    $item = $st->fetch();
    if (!$item) throw new Exception("Item not found");
    if ((int)$item['quantity'] < $qty) throw new Exception("Not enough stock");

    // Offer logic (simple): if offer code exists in offers table and active, apply percent discount
    $discountPct = 0.0;
    if ($offerCode !== '') {
      $st = $pdo->prepare("SELECT percent_off FROM offers WHERE code=? AND is_active=1 LIMIT 1");
      $st->execute([$offerCode]);
      $off = $st->fetch();
      if ($off) $discountPct = min(90.0, max(0.0, (float)$off['percent_off']));
    }

    $subtotal = (float)$item['price'] * $qty;
    $discountAmount = round($subtotal * ($discountPct / 100.0), 2);
    $total = max(0.0, $subtotal - $discountAmount);

    $totalCost = (float)$item['cost'] * $qty;
    $profit = $total - $totalCost;

    $st = $pdo->prepare("UPDATE items SET quantity = quantity - ? WHERE id=?");
    $st->execute([$qty, $itemId]);

    $st = $pdo->prepare("
      INSERT INTO orders(customer_user_id, item_id, item_code, item_name, item_emoji, quantity,
                        unit_price, unit_cost, subtotal, discount_pct, discount_amount, total_price, total_cost, profit,
                        payment_method, offer_code, status, created_at)
      VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'Pending', NOW())
    ");
    $st->execute([
      $user['id'], $itemId, $item['code'], $item['name'], $item['emoji'], $qty,
      $item['price'], $item['cost'], $subtotal, $discountPct, $discountAmount, $total, $totalCost, $profit,
      $paymentMethod, ($offerCode===''?null:$offerCode)
    ]);

    $pdo->commit();
    json_out(['ok'=>true]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    json_out(['ok'=>false,'error'=>$e->getMessage()], 400);
  }
  exit;
}

if ($method === 'PUT') {
  // Admin can update order status
  require_admin();
  require_csrf();

  $raw = json_decode(file_get_contents('php://input'), true) ?? [];
  $id = (int)($raw['id'] ?? 0);
  $status = (string)($raw['status'] ?? '');
  $allowed = ['Pending','Accepted','Delivered','Cancelled'];
  if ($id<=0 || !in_array($status, $allowed, true)) {
    json_out(['ok'=>false,'error'=>'Invalid input'], 400);
    exit;
  }

  $st = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
  $st->execute([$status, $id]);
  json_out(['ok'=>true]);
  exit;
}

json_out(['ok'=>false,'error'=>'Method not allowed'], 405);
