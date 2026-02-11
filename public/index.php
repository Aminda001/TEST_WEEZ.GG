<?php
declare(strict_types=1);
require __DIR__ . '/../lib/security.php';
require __DIR__ . '/../lib/csrf.php';

start_secure_session();
$csrf = csrf_token();
$app = (require __DIR__ . '/../lib/config.php')['app']['name'];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($app) ?> Secure Dashboard</title>
  <link rel="stylesheet" href="styles.css"/>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>"/>
</head>
<body>
  <div class="bg"></div>

  <header class="topbar">
    <div class="brand">
      <div class="logo">W</div>
      <div class="brandtext">
        <div class="name">WEEZ.GG</div>
        <div class="tag">Secure Admin + Customer Portal</div>
      </div>
    </div>
    <div class="top-actions">
      <span id="whoami" class="pill">Not logged in</span>
      <button id="btnLogout" class="btn ghost" hidden>Logout</button>
    </div>
  </header>

  <main class="shell">
    <aside class="sidebar" id="sidebar" hidden>
      <div class="side-title">Navigation</div>
      <nav class="nav" id="nav"></nav>
      <div class="side-foot">
        <div class="small">Currency: <b>LKR</b></div>
        <div class="small dim">Security: sessions + CSRF</div>
      </div>
    </aside>

    <section class="content">
      <div id="toast" class="toast" hidden></div>

      <!-- LOGIN -->
      <section id="viewLogin" class="card">
        <h1>Sign in</h1>
        <p class="muted">Use your account to access the portal.</p>

        <div class="grid2">
          <div class="field">
            <label>Username</label>
            <input id="loginUser" placeholder="e.g. Januk"/>
          </div>
          <div class="field">
            <label>Password</label>
            <input id="loginPass" type="password" placeholder="••••••••"/>
          </div>
        </div>

        <div class="row">
          <button id="btnLogin" class="btn primary">Login</button>
          <button id="btnDemo" class="btn">Use Demo Customer</button>
        </div>

        <div class="hint">
          Demo accounts (change after install):<br/>
          <b>Admins</b>: Januk / Tobi / Kitty<br/>
          <b>Customer</b>: customer
        </div>
      </section>

      <!-- DASHBOARD -->
      <section id="viewDashboard" class="stack" hidden>
        <div class="hero card">
          <div>
            <h2 id="dashTitle">Dashboard</h2>
            <div class="muted" id="dashSub">Overview</div>
          </div>
          <div class="row">
            <a class="btn" href="#/customer/shop" id="goShop" hidden>Go to Shop</a>
            <a class="btn" href="#/admin/items" id="goItems" hidden>Manage Items</a>
          </div>
        </div>

        <div class="cards" id="statCards"></div>

        <div class="card">
          <div class="row space">
            <h3>Recent Orders</h3>
            <a class="btn ghost" href="#/orders">View all</a>
          </div>
          <div class="tablewrap">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th><th>Item</th><th>Qty</th><th>Total (LKR)</th><th>Status</th><th>Created</th>
                </tr>
              </thead>
              <tbody id="recentOrders"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- SHOP -->
      <section id="viewShop" class="stack" hidden>
        <div class="card row space">
          <div>
            <h2>Shop</h2>
            <div class="muted">Customers can order items (cost is hidden).</div>
          </div>
          <div class="row">
            <input id="shopSearch" class="search" placeholder="Search items..."/>
            <button id="btnRefreshShop" class="btn">Refresh</button>
          </div>
        </div>

        <div class="grid" id="shopGrid"></div>
      </section>

      <!-- ORDERS -->
      <section id="viewOrders" class="card" hidden>
        <div class="row space">
          <div>
            <h2>Orders</h2>
            <div class="muted" id="ordersHint"></div>
          </div>
          <button id="btnRefreshOrders" class="btn">Refresh</button>
        </div>

        <div class="tablewrap">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th><th>Customer</th><th>Item</th><th>Qty</th><th>Total (LKR)</th><th>Offer</th><th>Method</th><th>Status</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="ordersTable"></tbody>
          </table>
        </div>
      </section>

      <!-- ADMIN ITEMS -->
      <section id="viewItems" class="stack" hidden>
        <div class="card">
          <div class="row space">
            <div>
              <h2>Items</h2>
              <div class="muted">Admin-only item manager.</div>
            </div>
            <button id="btnRefreshItems" class="btn">Refresh</button>
          </div>

          <div class="divider"></div>

          <h3>Add Item</h3>
          <div class="grid4">
            <div class="field"><label>Code</label><input id="itCode" placeholder="WEEZ-001"/></div>
            <div class="field"><label>Name</label><input id="itName" placeholder="Pro Gaming Mouse"/></div>
            <div class="field"><label>Cost (LKR)</label><input id="itCost" type="number" min="0" step="0.01"/></div>
            <div class="field"><label>Price (LKR)</label><input id="itPrice" type="number" min="0" step="0.01"/></div>
          </div>
          <div class="grid4">
            <div class="field"><label>Qty</label><input id="itQty" type="number" min="0" step="1"/></div>
            <div class="field"><label>Emoji</label><input id="itEmoji" placeholder="🖱️"/></div>
            <div class="field"><label>Category</label><input id="itCat" placeholder="Accessories"/></div>
            <div class="field"><label>Details</label><input id="itDetails" placeholder="Short description"/></div>
          </div>

          <div class="row">
            <button id="btnAddItem" class="btn primary">Add</button>
            <div class="muted small">Tip: Customers will only see <b>price</b>, not cost.</div>
          </div>
        </div>

        <div class="card">
          <div class="row space">
            <h3>All Items</h3>
            <span class="pill" id="itemsCount">0</span>
          </div>
          <div class="tablewrap">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th><th>Code</th><th>Name</th><th>Cost</th><th>Price</th><th>Qty</th><th>Category</th><th>Delete</th>
                </tr>
              </thead>
              <tbody id="itemsTable"></tbody>
            </table>
          </div>
        </div>
      </section>

      <footer class="foot">© <span id="year"></span> WEEZ.GG</footer>
    </section>
  </main>

  <script src="app.js"></script>
</body>
</html>
