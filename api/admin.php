<?php
declare(strict_types=1);

require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/security.php';
require __DIR__ . '/../lib/csrf.php';
require __DIR__ . '/../lib/response.php';

start_secure_session();
require_admin();
$pdo = db();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  // Dashboard stats (LKR)
  $row = $pdo->query("
    SELECT
      COALESCE(SUM(total_price),0) AS revenue,
      COALESCE(SUM(total_cost),0) AS cost,
      COALESCE(SUM(profit),0) AS profit
    FROM orders
    WHERE status IN ('Accepted','Delivered')
  ")->fetch();

  $salary_each = round(((float)$row['profit']) / 3.0, 2);

  $items = $pdo->query("SELECT COUNT(*) AS n FROM items")->fetch();
  $orders = $pdo->query("SELECT COUNT(*) AS n FROM orders")->fetch();
  $customers = $pdo->query("SELECT COUNT(*) AS n FROM users WHERE role='customer'")->fetch();

  json_out([
    'ok'=>true,
    'stats'=>[
      'revenue'=>(float)$row['revenue'],
      'cost'=>(float)$row['cost'],
      'profit'=>(float)$row['profit'],
      'salary_each'=>$salary_each,
      'items'=>(int)$items['n'],
      'orders'=>(int)$orders['n'],
      'customers'=>(int)$customers['n'],
    ]
  ]);
  exit;
}

json_out(['ok'=>false,'error'=>'Method not allowed'], 405);
