<?php
declare(strict_types=1);

/**
 * WEEZ.GG Secure PHP App
 * - Configure DB credentials below
 * - After install, set session.secure => true (requires HTTPS)
 */
return [
  'app' => [
    'name' => 'WEEZ.GG',
    'env'  => 'production', // 'development' or 'production'
  ],
  'db' => [
    'dsn'  => 'mysql:host=localhost;dbname=weezgg;charset=utf8mb4',
    'user' => 'root',
    'pass' => '',
  ],
  'session' => [
    'name' => 'WEEZGGSESSID',
    'secure' => false,   // set true on HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
  ],
  'install' => [
    // Visit /install.php?key=... once, then DELETE install.php
    'key' => '840e15dbf96cea5debd8259661eba585',
  ],
];
