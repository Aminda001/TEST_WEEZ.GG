<?php
declare(strict_types=1);

require __DIR__ . '/../lib/db.php';

$cfg = require __DIR__ . '/../lib/config.php';
$key = $_GET['key'] ?? '';
if (!hash_equals($cfg['install']['key'], (string)$key)) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

$pdo = db();

$sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
$pdo->exec($sql);

// Seed users
$users = [
  ['Januk','admin','januk@9865'],
  ['Tobi','admin','tobi@9865'],
  ['Kitty','admin','kitty@9865'],
  ['customer','customer','customer123'],
];

$st = $pdo->prepare("INSERT INTO users(username, role, password_hash) VALUES(?,?,?)");
foreach ($users as [$u,$role,$pass]) {
  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $st->execute([$u,$role,$hash]);
}

// Seed offers
$pdo->exec("INSERT INTO offers(code, percent_off, is_active) VALUES
  ('WEEZ10', 10, 1),
  ('WEEZ20', 20, 1)
");

// Seed items
$pdo->exec("INSERT INTO items(code,name,cost,price,quantity,details,emoji,category) VALUES
  ('WEEZ-001','Pro Gaming Mouse', 2500, 4900, 25, 'RGB mouse, lightweight, high DPI', '🖱️', 'Accessories'),
  ('WEEZ-002','Mechanical Keyboard', 6500, 9900, 15, 'Blue switches, durable keycaps', '⌨️', 'Accessories'),
  ('WEEZ-003','Gaming Headset', 4200, 7900, 18, 'Surround sound, comfy pads', '🎧', 'Audio')
");

echo "<h2>Installed ✅</h2>";
echo "<p>Now delete <b>public/install.php</b> for security.</p>";
echo "<p>Login demo:</p>";
echo "<ul>
<li>Admin: Januk / januk@9865</li>
<li>Admin: Tobi / tobi@9865</li>
<li>Admin: Kitty / kitty@9865</li>
<li>Customer: customer / customer123</li>
</ul>";
