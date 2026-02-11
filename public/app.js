
/* WEEZ.GG Secure SPA (Vanilla JS) */
const $ = (q, el=document) => el.querySelector(q);
const $$ = (q, el=document) => [...el.querySelectorAll(q)];

const API = {
  async csrf() {
    const r = await fetch('/api/auth.php?action=csrf', { credentials: 'include' });
    return r.json();
  },
  async me() {
    const r = await fetch('/api/auth.php?action=me', { credentials: 'include' });
    return r.json();
  },
  async login(username, password) {
    const r = await fetch('/api/auth.php?action=login', {
      method:'POST',
      credentials:'include',
      headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify({ username, password })
    });
    return r.json();
  },
  async logout(csrf) {
    const r = await fetch('/api/auth.php?action=logout', {
      method:'POST',
      credentials:'include',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({})
    });
    return r.json();
  },
  async items() {
    const r = await fetch('/api/items.php', { credentials:'include' });
    return r.json();
  },
  async addItem(csrf, item) {
    const r = await fetch('/api/items.php', {
      method:'POST', credentials:'include',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(item)
    });
    return r.json();
  },
  async deleteItem(csrf, id) {
    const r = await fetch('/api/items.php?id=' + encodeURIComponent(id), {
      method:'DELETE', credentials:'include',
      headers:{ 'X-CSRF-Token': csrf }
    });
    return r.json();
  },
  async orders() {
    const r = await fetch('/api/orders.php', { credentials:'include' });
    return r.json();
  },
  async createOrder(csrf, payload) {
    const r = await fetch('/api/orders.php', {
      method:'POST', credentials:'include',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(payload)
    });
    return r.json();
  },
  async setOrderStatus(csrf, id, status) {
    const r = await fetch('/api/orders.php', {
      method:'PUT', credentials:'include',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ id, status })
    });
    return r.json();
  },
  async adminStats() {
    const r = await fetch('/api/admin.php', { credentials:'include' });
    return r.json();
  }
};

const state = {
  csrf: $('meta[name="csrf-token"]').content,
  user: null,
  items: [],
  orders: []
};

function money(v){ 
  const n = Number(v||0);
  return 'LKR ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function toast(msg, kind='info'){
  const t = $('#toast');
  t.hidden = false;
  t.textContent = msg;
  t.style.borderColor = kind==='err' ? 'rgba(239,68,68,.6)' : 'rgba(99,102,241,.5)';
  t.style.background = kind==='err' ? 'rgba(239,68,68,.18)' : 'rgba(0,0,0,.5)';
  clearTimeout(toast._to);
  toast._to = setTimeout(()=> t.hidden=true, 3000);
}

function setWho(){
  const w = $('#whoami');
  const b = $('#btnLogout');
  if (!state.user){
    w.textContent = 'Not logged in';
    b.hidden = true;
  } else {
    w.textContent = `${state.user.username} • ${state.user.role}`;
    b.hidden = false;
  }
}

function show(viewId){
  const views = ['viewLogin','viewDashboard','viewShop','viewOrders','viewItems'];
  views.forEach(id => { const el = $('#'+id); if (el) el.hidden = (id!==viewId); });
}

function setSidebar(){
  const sb = $('#sidebar');
  const nav = $('#nav');
  if (!state.user){
    sb.hidden = true;
    nav.innerHTML = '';
    return;
  }
  sb.hidden = false;

  const links = [];
  links.push({ href:'#/dashboard', label:'Dashboard' });
  links.push({ href:'#/orders', label:'Orders' });

  if (state.user.role === 'admin'){
    links.push({ href:'#/admin/items', label:'Items' });
  } else {
    links.push({ href:'#/customer/shop', label:'Shop' });
  }

  nav.innerHTML = links.map(l => `<a href="${l.href}">${l.label}</a>`).join('');
}

function markActive(){
  const h = location.hash || '#/dashboard';
  $$('#nav a').forEach(a => a.classList.toggle('active', a.getAttribute('href') === h));
}

async function loadItems(){
  const r = await API.items();
  if (!r.ok){ toast(r.error||'Failed to load items','err'); return; }
  state.items = r.items || [];
}

async function loadOrders(){
  const r = await API.orders();
  if (!r.ok){ toast(r.error||'Failed to load orders','err'); return; }
  state.orders = r.orders || [];
}

function renderDashboard(){
  $('#dashTitle').textContent = state.user.role === 'admin' ? 'Admin Dashboard' : 'Customer Dashboard';
  $('#dashSub').textContent = state.user.role === 'admin' ? 'Financial overview and operations.' : 'Browse items and track your orders.';

  $('#goShop').hidden = state.user.role !== 'customer';
  $('#goItems').hidden = state.user.role !== 'admin';

  const cards = $('#statCards');
  cards.innerHTML = '';

  const base = [
    { k:'Items', v: state.items.length },
    { k:'Orders', v: state.orders.length },
  ];

  if (state.user.role === 'admin'){
    // We fetch admin stats separately (includes revenue/profit)
    base.push({ k:'Revenue', v: '…' });
    base.push({ k:'Net Profit', v: '…' });
  } else {
    const spent = state.orders.reduce((a,o)=>a + Number(o.total_price||0), 0);
    base.push({ k:'Total Spent', v: money(spent) });
    base.push({ k:'Status', v: 'Active' });
  }

  cards.innerHTML = base.map(c => `<div class="stat"><div class="k">${c.k}</div><div class="v">${c.v}</div></div>`).join('');

  // Recent orders table
  const tbody = $('#recentOrders');
  const recent = [...state.orders].slice(0,6);
  tbody.innerHTML = recent.map(o => `
    <tr>
      <td>#${o.id}</td>
      <td>${escapeHtml(o.item_emoji||'📦')} ${escapeHtml(o.item_name||'')}</td>
      <td>${o.quantity}</td>
      <td>${money(o.total_price)}</td>
      <td><span class="pill">${escapeHtml(o.status||'')}</span></td>
      <td>${escapeHtml(String(o.created_at||''))}</td>
    </tr>
  `).join('') || `<tr><td colspan="6" class="muted">No orders yet.</td></tr>`;
}

async function renderAdminStatsIntoCards(){
  const r = await API.adminStats();
  if (!r.ok) return;
  const stats = r.stats || {};
  const vs = $$('#statCards .stat .v');
  // We appended two placeholders at end for admin
  if (vs.length >= 4){
    vs[2].textContent = money(stats.revenue || 0);
    vs[3].textContent = money(stats.profit || 0);
  }
}

function renderShop(){
  const q = ($('#shopSearch').value || '').toLowerCase().trim();
  const grid = $('#shopGrid');
  const list = state.items.filter(it => {
    if (!q) return true;
    return (it.name||'').toLowerCase().includes(q) || (it.code||'').toLowerCase().includes(q) || (it.category||'').toLowerCase().includes(q);
  });

  grid.innerHTML = list.map(it => `
    <div class="card">
      <div class="row space">
        <div class="pill">${escapeHtml(it.code)}</div>
        <div class="pill">${escapeHtml(it.category||'General')}</div>
      </div>
      <h3 style="margin-top:10px">${escapeHtml(it.emoji||'📦')} ${escapeHtml(it.name)}</h3>
      <div class="muted" style="margin:6px 0 12px">${escapeHtml(it.details||'')}</div>
      <div class="row space">
        <div>
          <div class="muted small">Price</div>
          <div style="font-weight:900;font-size:18px">${money(it.price)}</div>
        </div>
        <div>
          <div class="muted small">Stock</div>
          <div style="font-weight:900">${it.quantity}</div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="grid2">
        <div class="field">
          <label>Qty</label>
          <input type="number" min="1" max="${it.quantity}" value="1" id="qty-${it.id}">
        </div>
        <div class="field">
          <label>Payment</label>
          <select id="pay-${it.id}">
            <option>Cash</option>
            <option>Bank Transfer</option>
            <option>Card</option>
          </select>
        </div>
      </div>

      <div class="grid2">
        <div class="field">
          <label>Offer Code (optional)</label>
          <input id="off-${it.id}" placeholder="e.g. WEEZ10">
        </div>
        <div class="field">
          <label>&nbsp;</label>
          <button class="btn primary" ${it.quantity<=0?'disabled':''} onclick="placeOrder(${it.id})">Buy Now</button>
        </div>
      </div>
    </div>
  `).join('') || `<div class="card muted">No matching items.</div>`;
}

window.placeOrder = async function(itemId){
  const it = state.items.find(x=>Number(x.id)===Number(itemId));
  if (!it) return;

  const qty = Number($('#qty-'+itemId).value || 1);
  const pay = $('#pay-'+itemId).value;
  const offerCode = ($('#off-'+itemId).value || '').trim();

  const r = await API.createOrder(state.csrf, { itemId, quantity: qty, paymentMethod: pay, offerCode });
  if (!r.ok){ toast(r.error||'Order failed','err'); return; }
  toast('Order placed successfully ✅');
  await refreshAll();
  location.hash = '#/orders';
};

function renderOrders(){
  $('#ordersHint').textContent = state.user.role === 'admin'
    ? 'All orders across customers.'
    : 'Your orders only.';

  const tb = $('#ordersTable');
  const rows = state.orders.map(o => {
    const customer = state.user.role==='admin' ? ('User#'+o.customer_user_id) : 'You';
    const action = state.user.role==='admin'
      ? `<select data-id="${o.id}" class="pill statusSel">
           <option ${o.status==='Pending'?'selected':''}>Pending</option>
           <option ${o.status==='Accepted'?'selected':''}>Accepted</option>
           <option ${o.status==='Delivered'?'selected':''}>Delivered</option>
           <option ${o.status==='Cancelled'?'selected':''}>Cancelled</option>
         </select>`
      : `<span class="pill">${escapeHtml(o.status||'')}</span>`;

    return `
      <tr>
        <td>#${o.id}</td>
        <td>${escapeHtml(customer)}</td>
        <td>${escapeHtml(o.item_emoji||'📦')} ${escapeHtml(o.item_name||'')}</td>
        <td>${o.quantity}</td>
        <td>${money(o.total_price)}</td>
        <td>${escapeHtml(o.offer_code || '')}</td>
        <td>${escapeHtml(o.payment_method||'')}</td>
        <td>${escapeHtml(o.status||'')}</td>
        <td>${action}</td>
      </tr>
    `;
  }).join('');

  tb.innerHTML = rows || `<tr><td colspan="9" class="muted">No orders yet.</td></tr>`;

  // Bind status change
  $$('.statusSel').forEach(sel => {
    sel.addEventListener('change', async (e)=>{
      const id = Number(sel.dataset.id);
      const status = sel.value;
      const r = await API.setOrderStatus(state.csrf, id, status);
      if (!r.ok){ toast(r.error||'Failed','err'); return; }
      toast('Status updated');
      await refreshAll();
      renderOrders();
    });
  });
}

function renderItemsAdmin(){
  const tb = $('#itemsTable');
  $('#itemsCount').textContent = String(state.items.length);

  tb.innerHTML = state.items.map(it => `
    <tr>
      <td>#${it.id}</td>
      <td>${escapeHtml(it.code||'')}</td>
      <td>${escapeHtml(it.emoji||'📦')} ${escapeHtml(it.name||'')}</td>
      <td class="muted">(hidden)</td>
      <td>${money(it.price)}</td>
      <td>${it.quantity}</td>
      <td>${escapeHtml(it.category||'')}</td>
      <td><button class="btn danger" onclick="delItem(${it.id})">Delete</button></td>
    </tr>
  `).join('') || `<tr><td colspan="8" class="muted">No items.</td></tr>`;
}

window.delItem = async function(id){
  const r = await API.deleteItem(state.csrf, id);
  if (!r.ok){ toast(r.error||'Delete failed','err'); return; }
  toast('Item deleted');
  await refreshAll();
  renderItemsAdmin();
};

async function refreshAll(){
  await loadItems();
  await loadOrders();
}

function escapeHtml(s){
  return String(s ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

async function route(){
  const h = location.hash || '#/dashboard';
  markActive();

  if (!state.user){
    show('viewLogin');
    return;
  }

  if (h === '#/dashboard'){
    show('viewDashboard');
    renderDashboard();
    if (state.user.role === 'admin') await renderAdminStatsIntoCards();
    return;
  }

  if (h === '#/orders'){
    show('viewOrders');
    renderOrders();
    return;
  }

  if (h === '#/customer/shop' && state.user.role === 'customer'){
    show('viewShop');
    renderShop();
    return;
  }

  if (h === '#/admin/items' && state.user.role === 'admin'){
    show('viewItems');
    renderItemsAdmin();
    return;
  }

  // Fallback
  location.hash = '#/dashboard';
}

async function bootstrap(){
  $('#year').textContent = String(new Date().getFullYear());

  $('#btnLogin').addEventListener('click', async ()=>{
    const u = $('#loginUser').value.trim();
    const p = $('#loginPass').value;
    const r = await API.login(u,p);
    if (!r.ok){ toast(r.error||'Login failed','err'); return; }
    state.user = r.user;
    setWho();
    setSidebar();
    await refreshAll();
    location.hash = '#/dashboard';
    await route();
  });

  $('#btnDemo').addEventListener('click', ()=>{
    $('#loginUser').value = 'customer';
    $('#loginPass').value = 'customer123';
  });

  $('#btnLogout').addEventListener('click', async ()=>{
    const r = await API.logout(state.csrf);
    if (!r.ok){ toast(r.error||'Logout failed','err'); return; }
    state.user = null;
    setWho();
    setSidebar();
    location.hash = '#/login';
    toast('Logged out');
    await route();
  });

  $('#btnRefreshShop').addEventListener('click', async ()=>{
    await loadItems();
    renderShop();
  });

  $('#shopSearch').addEventListener('input', renderShop);

  $('#btnRefreshOrders').addEventListener('click', async ()=>{
    await loadOrders();
    renderOrders();
  });

  $('#btnRefreshItems').addEventListener('click', async ()=>{
    await loadItems();
    renderItemsAdmin();
  });

  $('#btnAddItem').addEventListener('click', async ()=>{
    const item = {
      code: $('#itCode').value.trim(),
      name: $('#itName').value.trim(),
      cost: Number($('#itCost').value || 0),
      price: Number($('#itPrice').value || 0),
      quantity: Number($('#itQty').value || 0),
      emoji: $('#itEmoji').value.trim() || '📦',
      category: $('#itCat').value.trim() || 'General',
      details: $('#itDetails').value.trim()
    };
    const r = await API.addItem(state.csrf, item);
    if (!r.ok){ toast(r.error||'Add item failed','err'); return; }
    toast('Item added');
    ['#itCode','#itName','#itCost','#itPrice','#itQty','#itEmoji','#itCat','#itDetails'].forEach(s=>$(s).value='');
    await refreshAll();
    renderItemsAdmin();
  });

  // Determine current session
  const me = await API.me();
  if (me.ok && me.user){
    state.user = me.user;
    setWho();
    setSidebar();
    await refreshAll();
    location.hash = '#/dashboard';
  } else {
    location.hash = '#/login';
  }

  window.addEventListener('hashchange', route);
  await route();
}

bootstrap().catch(e => {
  console.error(e);
  toast('App error: ' + e.message, 'err');
});
