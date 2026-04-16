<?php
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
$name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Paradise Resort</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  :root {
    --teal:#009688; --teal-dark:#00796B; --teal-light:#4DB6AC;
    --gold:#FFC107; --red:#f44336; --green:#4CAF50;
    --bg:#f0f7f6; --white:#fff; --text:#1a2e2e;
    --shadow:0 4px 24px rgba(0,150,136,0.10);
    --sidebar-w:240px;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Lato',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
  .sidebar {
    width:var(--sidebar-w); background:var(--white);
    border-right:1px solid #e0f2f1;
    display:flex; flex-direction:column;
    position:fixed; top:0; left:0; bottom:0; z-index:50;
    box-shadow:2px 0 16px rgba(0,150,136,0.06);
  }
  .sidebar-brand { padding:24px 20px; border-bottom:1px solid #e0f2f1; }
  .sidebar-brand h2 { font-family:'Playfair Display',serif; font-size:1rem; color:var(--teal-dark); }
  .sidebar-brand p { font-size:0.78rem; color:#888; margin-top:2px; }
  .sidebar-nav { padding:20px 12px; flex:1; }
  .nav-item {
    display:flex; align-items:center; gap:12px;
    padding:11px 16px; border-radius:12px;
    font-size:0.9rem; font-weight:700; color:#666;
    cursor:pointer; transition:all 0.2s; margin-bottom:4px;
    border:none; background:none; width:100%; text-align:left;
  }
  .nav-item:hover { background:#e0f2f1; color:var(--teal); }
  .nav-item.active { background:var(--teal); color:#fff; }
  .main-content { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
  .topbar {
    background:var(--white); padding:0 36px; height:68px;
    display:flex; align-items:center; justify-content:space-between;
    border-bottom:1px solid #e0f2f1; position:sticky; top:0; z-index:40;
  }
  .topbar h1 { font-family:'Playfair Display',serif; font-size:1.4rem; color:var(--text); }
  .topbar-right { display:flex; align-items:center; gap:16px; }
  .admin-badge {
    background:var(--teal-light); color:#fff;
    padding:4px 14px; border-radius:20px; font-size:0.78rem; font-weight:700;
  }
  .btn-logout {
    background:#fff; color:#888; border:1.5px solid #ddd;
    padding:7px 18px; border-radius:30px; cursor:pointer;
    font-size:0.85rem; font-weight:700; transition:all 0.2s;
  }
  .btn-logout:hover { background:var(--red); color:#fff; border-color:var(--red); }
  .content { padding:30px 36px 60px; }
  .stats-grid {
    display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px;
    margin-bottom:28px;
  }
  .stat-card {
    background:var(--white); border-radius:16px; padding:24px;
    box-shadow:var(--shadow); display:flex; align-items:flex-start; justify-content:space-between;
    transition:transform 0.2s;
  }
  .stat-card:hover { transform:translateY(-4px); }
  .stat-label { font-size:0.82rem; color:#888; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; }
  .stat-value { font-size:2rem; font-weight:700; margin-top:6px; color:var(--text); }
  .stat-sub { font-size:0.78rem; color:#aaa; margin-top:4px; }
  .panels-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px; }
  @media(max-width:900px) { .panels-grid { grid-template-columns:1fr; } }
  .charts-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:20px; margin-bottom:28px; }
  @media(max-width:900px) { .charts-grid { grid-template-columns:1fr; } }
  .panel { background:var(--white); border-radius:16px; padding:24px; box-shadow:var(--shadow); }
  .chart-panel h3 { margin-bottom:6px; }
  .chart-panel p { color:#888; font-size:0.82rem; margin-bottom:14px; }
  .chart-wrap {
    background:linear-gradient(180deg, rgba(77,182,172,0.12), rgba(255,255,255,0.65));
    border:1px solid rgba(0,150,136,0.10);
    border-radius:16px;
    padding:14px;
  }
  .chart-canvas {
    width:100%;
    height:260px;
    display:block;
  }
  .chart-legend {
    display:flex;
    gap:14px;
    flex-wrap:wrap;
    margin-top:14px;
  }
  .chart-legend span {
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-size:0.8rem;
    color:#566;
    font-weight:700;
  }
  .chart-legend i {
    width:10px;
    height:10px;
    border-radius:50%;
    display:inline-block;
  }
  .inventory-row, .status-row {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 0; border-bottom:1px solid #f5f5f5;
  }
  .inventory-row:last-child, .status-row:last-child { border-bottom:none; }
  .inv-type { font-size:0.88rem; color:#555; }
  .inv-count, .status-count { font-size:1.1rem; font-weight:700; color:var(--teal); }
  .table-panel { background:var(--white); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:28px; }
  .table-toolbar {
    display:flex;
    justify-content:flex-end;
    padding:12px 20px;
    border-bottom:1px solid #f2f2f2;
    background:#fff;
  }
  .btn-add-cottage {
    background:var(--teal);
    color:#fff;
    border:none;
    border-radius:30px;
    padding:8px 16px;
    font-size:0.82rem;
    font-weight:700;
    cursor:pointer;
  }
  .btn-add-cottage:hover { background:var(--teal-dark); }
  .filter-btn {
    padding:6px 14px; border-radius:20px; font-size:0.78rem;
    font-weight:700; border:none; cursor:pointer; transition:all 0.2s;
    background:#f5f5f5; color:#666;
  }
  .filter-btn.active { background:var(--teal); color:#fff; }
  .report-toolbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:20px;
    flex-wrap:wrap;
  }
  .report-range {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
  }
  .report-note {
    font-size:0.85rem;
    color:#6d7a7a;
    font-weight:700;
  }
  .report-month-select {
    display:none;
    min-width:170px;
    padding:10px 14px;
    border-radius:14px;
    border:1px solid #d7e9e7;
    background:#fff;
    color:#194040;
    font-size:0.86rem;
    font-weight:700;
    outline:none;
  }
  .report-month-select.show { display:block; }
  table { width:100%; border-collapse:collapse; }
  thead tr { background:#f5fafa; }
  th { padding:12px 20px; text-align:left; font-size:0.78rem; font-weight:700; color:#888; text-transform:uppercase; }
  td { padding:14px 20px; font-size:0.88rem; border-bottom:1px solid #f8f8f8; }
  .status-pill {
    display:inline-block; padding:3px 12px; border-radius:20px;
    font-size:0.75rem; font-weight:700;
  }
  .sp-pending { background:#FFF8E1; color:#F57F17; }
  .sp-confirmed { background:#E8F5E9; color:#2E7D32; }
  .sp-cancelled { background:#FFEBEE; color:#C62828; }
  .action-btn {
    padding:5px 12px; border-radius:15px; font-size:0.75rem; font-weight:700;
    cursor:pointer; border:none; transition:all 0.2s; margin-right:4px;
  }
  .btn-confirm-a { background:#E8F5E9; color:#2E7D32; }
  .btn-cancel-a { background:#FFEBEE; color:#C62828; }
  .btn-edit-a { background:#E3F2FD; color:#1565C0; }
  .btn-edit-a:hover { background:#cfe8ff; transform:translateY(-1px); }
  .page { display:none; }
  .page.active { display:block; }
  .res-filters { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
  .modal-overlay {
    position:fixed;
    inset:0;
    background:rgba(7, 31, 31, 0.45);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:120;
    padding:20px;
  }
  .modal-overlay.active { display:flex; }
  .admin-modal {
    width:min(100%, 460px);
    background:#fff;
    border-radius:22px;
    box-shadow:0 24px 60px rgba(0,0,0,0.18);
    overflow:hidden;
  }
  .admin-modal-head {
    padding:20px 24px 14px;
    background:linear-gradient(135deg, rgba(0,150,136,0.10), rgba(77,182,172,0.16));
    border-bottom:1px solid #ebf3f3;
  }
  .admin-modal-head h3 {
    font-family:'Playfair Display',serif;
    color:var(--teal-dark);
    font-size:1.35rem;
    margin-bottom:4px;
  }
  .admin-modal-head p {
    color:#688080;
    font-size:0.84rem;
  }
  .admin-modal-body { padding:20px 24px 24px; }
  .admin-form-grid {
    display:grid;
    gap:14px;
  }
  .admin-form-group label {
    display:block;
    font-size:0.8rem;
    font-weight:700;
    color:#5f7171;
    margin-bottom:6px;
  }
  .admin-form-group input,
  .admin-form-group select,
  .admin-form-group textarea {
    width:100%;
    border:1.5px solid #d8e8e7;
    background:#fbfefe;
    border-radius:14px;
    padding:12px 14px;
    font-size:0.92rem;
    color:#173c3c;
    outline:none;
    transition:border-color 0.2s, box-shadow 0.2s, background 0.2s;
  }
  .admin-form-group input:hover,
  .admin-form-group select:hover,
  .admin-form-group textarea:hover {
    border-color:#9fd5cf;
    background:#fff;
  }
  .admin-form-group input:focus,
  .admin-form-group select:focus,
  .admin-form-group textarea:focus {
    border-color:var(--teal);
    box-shadow:0 0 0 4px rgba(0,150,136,0.10);
    background:#fff;
  }
  .admin-form-group textarea {
    resize:vertical;
    min-height:110px;
  }
  .admin-modal-actions {
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:18px;
  }
  .modal-btn {
    border:none;
    border-radius:999px;
    padding:11px 18px;
    font-size:0.85rem;
    font-weight:700;
    cursor:pointer;
  }
  .modal-btn.secondary {
    background:#f2f6f6;
    color:#657979;
  }
  .modal-btn.primary {
    background:var(--teal);
    color:#fff;
  }
  .modal-btn.primary:hover { background:var(--teal-dark); }
  .modal-btn.secondary:hover { background:#e7efef; }
  .admin-modal-error {
    margin-top:14px;
    color:#c62828;
    font-size:0.82rem;
    font-weight:700;
    display:none;
  }
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-brand">
    <h2>Admin Panel</h2>
    <p>Management Console</p>
  </div>
  <nav class="sidebar-nav">
    <button class="nav-item active" id="nav-dashboard" onclick="showPage('dashboard')">Dashboard</button>
    <button class="nav-item" id="nav-reservations" onclick="showPage('reservations')">Reservations</button>
    <button class="nav-item" id="nav-cottages" onclick="showPage('cottages')">Cottages</button>
    <button class="nav-item" id="nav-reports" onclick="showPage('reports')">Reports</button>
  </nav>
</aside>

<div class="main-content">
  <div class="topbar">
    <div>
      <h1 id="page-title">Admin Dashboard</h1>
      <p style="font-size:0.82rem;color:#888;">Overview of your resort cottage reservation system</p>
    </div>
    <div class="topbar-right">
      <span><?= htmlspecialchars($name) ?></span>
      <span class="admin-badge">Admin</span>
      <button class="btn-logout" onclick="logout()">Logout</button>
    </div>
  </div>

  <div class="content">
    <div class="page active" id="page-dashboard">
      <div class="stats-grid">
        <div class="stat-card"><div><div class="stat-label">Total Reservations</div><div class="stat-value" id="stat-total">0</div></div></div>
        <div class="stat-card"><div><div class="stat-label">Pending Approval</div><div class="stat-value" id="stat-pending">0</div></div></div>
        <div class="stat-card"><div><div class="stat-label">Confirmed</div><div class="stat-value" id="stat-confirmed">0</div></div></div>
        <div class="stat-card"><div><div class="stat-label">Total Revenue</div><div class="stat-value" id="stat-revenue">PHP 0</div></div></div>
      </div>

      <div class="panels-grid">
        <div class="panel">
          <h3>Cottage Inventory</h3>
          <div id="inventory-list">Loading...</div>
        </div>
        <div class="panel">
          <h3>Booking Status Overview</h3>
          <div class="status-row"><span>Pending</span><span class="status-count" id="ov-pending">0</span></div>
          <div class="status-row"><span>Confirmed</span><span class="status-count" id="ov-confirmed">0</span></div>
          <div class="status-row"><span>Cancelled</span><span class="status-count" id="ov-cancelled">0</span></div>
          <div class="status-row"><span>Today's Bookings</span><span class="status-count" id="ov-today">0</span></div>
        </div>
      </div>

      <div class="charts-grid">
        <div class="panel chart-panel">
          <h3>Reservation Status Chart</h3>
          <p>Live booking distribution across pending, confirmed, and cancelled reservations.</p>
          <div class="chart-wrap">
            <canvas id="status-chart" class="chart-canvas" width="640" height="260"></canvas>
            <div class="chart-legend">
              <span><i style="background:#f6b93b"></i>Pending</span>
              <span><i style="background:#38b46a"></i>Confirmed</span>
              <span><i style="background:#f15b5b"></i>Cancelled</span>
            </div>
          </div>
        </div>

        <div class="panel chart-panel">
          <h3>Cottage Type Chart</h3>
          <p>Current available inventory grouped by cottage type.</p>
          <div class="chart-wrap">
            <canvas id="cottage-chart" class="chart-canvas" width="520" height="260"></canvas>
            <div class="chart-legend">
              <span><i style="background:#009688"></i>Small</span>
              <span><i style="background:#4db6ac"></i>Medium</span>
              <span><i style="background:#1e847f"></i>Large</span>
              <span><i style="background:#7ad3ca"></i>Family</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="page" id="page-reservations">
      <div class="res-filters">
        <button class="filter-btn active" onclick="filterReservations('all',this)">All</button>
        <button class="filter-btn" onclick="filterReservations('pending',this)">Pending</button>
        <button class="filter-btn" onclick="filterReservations('confirmed',this)">Confirmed</button>
        <button class="filter-btn" onclick="filterReservations('cancelled',this)">Cancelled</button>
      </div>
      <div class="table-panel">
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Cottage</th>
              <th>Check-In</th>
              <th>Check-Out</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="reservations-tbody">
            <tr><td colspan="8" style="text-align:center;color:#aaa;padding:30px;">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="page" id="page-cottages">
      <div class="table-panel">
        <div class="table-toolbar">
          <button class="btn-add-cottage" onclick="addCottage()">+ Add Cottage</button>
        </div>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Capacity</th>
              <th>Price</th>
              <th>Unit</th>
              <th>Amenities</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="cottages-tbody">
            <tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="page" id="page-reports">
      <div class="report-toolbar">
        <div class="report-range">
          <button class="filter-btn active" id="report-day" onclick="setReportPeriod('day', this)">Daily</button>
          <button class="filter-btn" id="report-week" onclick="setReportPeriod('week', this)">Weekly</button>
          <button class="filter-btn" id="report-month" onclick="setReportPeriod('month', this)">Monthly</button>
          <select id="report-month-select" class="report-month-select" onchange="changeReportMonth(this.value)"></select>
        </div>
        <div class="report-note" id="report-period-label">Today's booking report</div>
      </div>

      <div class="stats-grid">
        <div class="stat-card"><div><div class="stat-label">Total Bookings</div><div class="stat-value" id="rep-total">0</div></div></div>
        <div class="stat-card"><div><div class="stat-label">Confirmed Revenue</div><div class="stat-value" id="rep-revenue">PHP 0</div></div></div>
        <div class="stat-card"><div><div class="stat-label">Cancellation Rate</div><div class="stat-value" id="rep-cancel-rate">0%</div></div></div>
        <div class="stat-card"><div><div class="stat-label">Confirmation Rate</div><div class="stat-value" id="rep-confirm-rate">0%</div></div></div>
      </div>

      <div class="table-panel">
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Cottage</th>
              <th>Booked On</th>
              <th>Stay Dates</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="reports-tbody">
            <tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">Loading report...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="update-modal">
  <div class="admin-modal">
    <div class="admin-modal-head">
      <h3>Update Cottage Details</h3>
      <p>Adjust the price, unit, and amenities for this cottage.</p>
    </div>
    <div class="admin-modal-body">
      <div class="admin-form-grid">
        <div class="admin-form-group">
          <label for="update-cottage-name">Cottage</label>
          <input id="update-cottage-name" type="text" readonly>
        </div>
        <div class="admin-form-group">
          <label for="update-cottage-price">Price</label>
          <input id="update-cottage-price" type="number" min="1" step="0.01">
        </div>
        <div class="admin-form-group">
          <label for="update-cottage-unit">Unit</label>
          <select id="update-cottage-unit">
            <option value="day">Day</option>
            <option value="night">Night</option>
            <option value="event">Event</option>
          </select>
        </div>
        <div class="admin-form-group">
          <label for="update-cottage-amenities">Amenities</label>
          <textarea id="update-cottage-amenities" placeholder="WiFi, AC, Grill"></textarea>
        </div>
      </div>
      <div class="admin-modal-error" id="update-modal-error"></div>
      <div class="admin-modal-actions">
        <button class="modal-btn secondary" type="button" onclick="closeUpdateModal()">Cancel</button>
        <button class="modal-btn primary" type="button" onclick="submitCottageUpdate()">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script>
let allBookings = [];
let statsData = null;
let allCottages = [];
let currentReportPeriod = 'day';
let selectedReportMonth = new Date().getMonth();
let updatingCottageId = null;

function showPage(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('page-'+page).classList.add('active');
  document.getElementById('nav-'+page).classList.add('active');
  const titles = {dashboard:'Admin Dashboard',reservations:'Reservations',cottages:'Cottages',reports:'Reports'};
  document.getElementById('page-title').textContent = titles[page] || page;
  if (page === 'reservations') loadReservations();
  if (page === 'cottages') loadCottages();
  if (page === 'reports') loadReports();
}

async function loadStats() {
  const fd = new FormData();
  fd.append('action','stats');
  const res = await fetch('reservations.php', {method:'POST',body:fd});
  const data = await res.json();
  if (!data.success) return;
  const s = data.stats;
  statsData = s;

  document.getElementById('stat-total').textContent = s.total;
  document.getElementById('stat-pending').textContent = s.pending;
  document.getElementById('stat-confirmed').textContent = s.confirmed;
  document.getElementById('stat-revenue').textContent = 'PHP ' + Number(s.revenue).toLocaleString();
  document.getElementById('ov-pending').textContent = s.pending;
  document.getElementById('ov-confirmed').textContent = s.confirmed;
  document.getElementById('ov-cancelled').textContent = s.cancelled;
  document.getElementById('ov-today').textContent = s.today;

  const invEl = document.getElementById('inventory-list');
  const cottages = s.cottages || [];
  invEl.innerHTML = cottages.length
    ? cottages.map(c => `
    <div class="inventory-row">
      <div class="inv-type">${c.type} Cottages</div>
      <div class="inv-count">${c.count}</div>
    </div>
  `).join('')
    : '<p style="color:#aaa">No cottage data</p>';

  renderCharts();
}

async function loadReservations() {
  const fd = new FormData();
  fd.append('action','all');
  const res = await fetch('reservations.php', {method:'POST',body:fd});
  const data = await res.json();
  if (!data.success) return;
  allBookings = data.bookings;
  renderReservations(allBookings);
}

async function ensureBookingsLoaded() {
  if (allBookings.length) return;
  await loadReservations();
}

function renderReservations(bookings) {
  const tbody = document.getElementById('reservations-tbody');
  if (!bookings.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#aaa;padding:30px;">No reservations found</td></tr>';
    return;
  }
  tbody.innerHTML = bookings.map(b => `
    <tr>
      <td>${b.booking_id}</td>
      <td><div style="font-weight:700">${b.customer_name}</div><div style="font-size:0.75rem;color:#aaa">${b.customer_email}</div></td>
      <td><div style="font-weight:700">${b.cottage_name}</div><div style="font-size:0.75rem;color:#aaa">${b.cottage_type}</div></td>
      <td>${b.check_in}</td>
      <td>${b.check_out}</td>
      <td style="font-weight:700;color:var(--teal)">PHP ${Number(b.total_amount).toLocaleString()}</td>
      <td><span class="status-pill sp-${b.status}">${b.status}</span></td>
      <td>
        ${b.status === 'pending' ? `<button class="action-btn btn-confirm-a" onclick="confirmBooking('${b.booking_id}')">Confirm</button>` : ''}
        ${b.status !== 'cancelled' ? `<button class="action-btn btn-cancel-a" onclick="cancelBooking('${b.booking_id}')">Cancel</button>` : ''}
      </td>
    </tr>
  `).join('');
}

function filterReservations(status, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderReservations(status === 'all' ? allBookings : allBookings.filter(b => b.status === status));
}

async function confirmBooking(bookingId) {
  const fd = new FormData();
  fd.append('action','confirm');
  fd.append('booking_id',bookingId);
  const res = await fetch('reservations.php', {method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { loadReservations(); loadStats(); }
  else alert(data.message || 'Failed');
}

async function cancelBooking(bookingId) {
  if (!confirm('Cancel this booking?')) return;
  const fd = new FormData();
  fd.append('action','cancel');
  fd.append('booking_id',bookingId);
  const res = await fetch('reservations.php', {method:'POST',body:fd});
  const data = await res.json();
  if (data.success) { loadReservations(); loadStats(); }
  else alert(data.message || 'Failed');
}

function escapeForSingleQuote(value) {
  return String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function getCanvasContext(id) {
  const canvas = document.getElementById(id);
  if (!canvas) return null;
  const dpr = window.devicePixelRatio || 1;
  const width = canvas.clientWidth || canvas.width;
  const height = canvas.clientHeight || canvas.height;
  canvas.width = Math.round(width * dpr);
  canvas.height = Math.round(height * dpr);
  const ctx = canvas.getContext('2d');
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  return { canvas, ctx, width, height };
}

function drawEmptyState(ctx, width, height, message) {
  ctx.clearRect(0, 0, width, height);
  ctx.fillStyle = '#95a5a6';
  ctx.font = '600 14px Lato';
  ctx.textAlign = 'center';
  ctx.fillText(message, width / 2, height / 2);
}

function drawStatusChart() {
  const chart = getCanvasContext('status-chart');
  if (!chart) return;

  const { ctx, width, height } = chart;
  const pending = Number(statsData?.pending || 0);
  const confirmed = Number(statsData?.confirmed || 0);
  const cancelled = Number(statsData?.cancelled || 0);
  const total = pending + confirmed + cancelled;

  if (!total) {
    drawEmptyState(ctx, width, height, 'No reservation data yet');
    return;
  }

  ctx.clearRect(0, 0, width, height);
  const colors = ['#f6b93b', '#38b46a', '#f15b5b'];
  const values = [pending, confirmed, cancelled];
  const labels = ['Pending', 'Confirmed', 'Cancelled'];
  const centerX = width * 0.32;
  const centerY = height * 0.54;
  const radius = Math.min(width, height) * 0.27;
  const innerRadius = radius * 0.58;
  let startAngle = -Math.PI / 2;

  values.forEach((value, index) => {
    const slice = (value / total) * Math.PI * 2;
    ctx.beginPath();
    ctx.moveTo(centerX, centerY);
    ctx.arc(centerX, centerY, radius, startAngle, startAngle + slice);
    ctx.closePath();
    ctx.fillStyle = colors[index];
    ctx.fill();
    startAngle += slice;
  });

  ctx.globalCompositeOperation = 'destination-out';
  ctx.beginPath();
  ctx.arc(centerX, centerY, innerRadius, 0, Math.PI * 2);
  ctx.fill();
  ctx.globalCompositeOperation = 'source-over';

  ctx.fillStyle = '#173c3c';
  ctx.font = '700 28px Lato';
  ctx.textAlign = 'center';
  ctx.fillText(String(total), centerX, centerY - 4);
  ctx.font = '600 12px Lato';
  ctx.fillStyle = '#6d7a7a';
  ctx.fillText('Total Bookings', centerX, centerY + 18);

  labels.forEach((label, index) => {
    const y = 72 + index * 58;
    ctx.fillStyle = colors[index];
    ctx.beginPath();
    ctx.roundRect(width * 0.62, y - 10, 14, 14, 5);
    ctx.fill();

    ctx.fillStyle = '#1f3e3d';
    ctx.font = '700 14px Lato';
    ctx.textAlign = 'left';
    ctx.fillText(label, width * 0.62 + 24, y + 1);
    ctx.fillStyle = '#6d7a7a';
    ctx.font = '600 12px Lato';
    ctx.fillText(`${values[index]} booking${values[index] === 1 ? '' : 's'}`, width * 0.62 + 24, y + 20);
  });
}

function drawCottageChart() {
  const chart = getCanvasContext('cottage-chart');
  if (!chart) return;

  const { ctx, width, height } = chart;
  const raw = statsData?.cottages || [];
  const colors = {
    Small: '#009688',
    Medium: '#4db6ac',
    Large: '#1e847f',
    Family: '#7ad3ca'
  };

  if (!raw.length) {
    drawEmptyState(ctx, width, height, 'No cottage inventory data yet');
    return;
  }

  ctx.clearRect(0, 0, width, height);
  const left = 50;
  const right = width - 20;
  const top = 28;
  const bottom = height - 40;
  const chartHeight = bottom - top;
  const maxValue = Math.max(...raw.map(item => Number(item.count || 0)), 1);
  const barWidth = 46;
  const gap = (right - left - (barWidth * raw.length)) / Math.max(raw.length, 1);

  ctx.strokeStyle = 'rgba(0, 150, 136, 0.12)';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = top + (chartHeight / 4) * i;
    ctx.beginPath();
    ctx.moveTo(left, y);
    ctx.lineTo(right, y);
    ctx.stroke();
  }

  raw.forEach((item, index) => {
    const count = Number(item.count || 0);
    const barHeight = (count / maxValue) * (chartHeight - 14);
    const x = left + gap * index + barWidth * index + gap / 2;
    const y = bottom - barHeight;
    const color = colors[item.type] || '#009688';

    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.roundRect(x, y, barWidth, barHeight, 12);
    ctx.fill();

    ctx.fillStyle = '#173c3c';
    ctx.font = '700 13px Lato';
    ctx.textAlign = 'center';
    ctx.fillText(String(count), x + (barWidth / 2), y - 8);
    ctx.fillStyle = '#607171';
    ctx.font = '600 12px Lato';
    ctx.fillText(item.type, x + (barWidth / 2), bottom + 18);
  });
}

function renderCharts() {
  drawStatusChart();
  drawCottageChart();
}

async function loadCottages() {
  const res = await fetch('cottages.php?action=list');
  const data = await res.json();
  const tbody = document.getElementById('cottages-tbody');
  if (!data.success || !data.cottages.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#aaa">No cottages</td></tr>';
    return;
  }
  allCottages = data.cottages;
  tbody.innerHTML = data.cottages.map(c => `
    <tr>
      <td>
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <img
            src="${c.image_url || '/paradise/assets/cottages/paradise-nook-cottage.jpg'}"
            alt="${c.name}"
            style="width:70px;height:52px;object-fit:cover;border-radius:10px;flex-shrink:0;"
            onerror="this.src='/paradise/assets/cottages/paradise-nook-cottage.jpg'"
          >
          <div>
            <div style="font-weight:700">${c.name}</div>
            <div style="font-size:0.75rem;color:#aaa">${c.description || '-'}</div>
          </div>
        </div>
      </td>
      <td>${c.type}</td>
      <td>${c.capacity} guests</td>
      <td style="font-weight:700;color:var(--teal)">PHP ${Number(c.price).toLocaleString()}</td>
      <td>${c.pricing_unit}</td>
      <td style="font-size:0.75rem;color:#888">${c.amenities || '-'}</td>
      <td>
        <button class="action-btn btn-edit-a" onclick="updateCottageDetails(${c.id})">Update</button>
      </td>
    </tr>
  `).join('');
}

function promptCottageData(existing = null) {
  const name = prompt('Cottage name:', existing?.name || '');
  if (name === null) return null;
  const description = prompt('Description:', existing?.description || '') ?? '';
  const type = prompt('Type (Small, Medium, Large, Family):', existing?.type || 'Small');
  if (type === null) return null;
  const capacity = prompt('Capacity (number):', String(existing?.capacity ?? 2));
  if (capacity === null) return null;
  const price = prompt('Price (number):', String(existing?.price ?? 3000));
  if (price === null) return null;
  const pricingUnit = prompt('Pricing unit (day, night, event):', existing?.pricing_unit || 'night');
  if (pricingUnit === null) return null;
  const amenities = prompt('Amenities (comma separated):', existing?.amenities || '') ?? '';
  const imageUrl = prompt('Image URL or local path:', existing?.image_url || '') ?? '';

  return {
    name: name.trim(),
    description: description.trim(),
    type: type.trim(),
    capacity: String(capacity).trim(),
    price: String(price).trim(),
    pricing_unit: pricingUnit.trim(),
    amenities: amenities.trim(),
    image_url: imageUrl.trim()
  };
}

async function addCottage() {
  const data = promptCottageData();
  if (!data) return;

  const fd = new FormData();
  fd.append('action', 'add');
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));

  const res = await fetch('cottages.php', { method: 'POST', body: fd });
  const json = await res.json();
  if (!json.success) {
    alert(json.message || 'Failed to add cottage');
    return;
  }
  loadCottages();
  loadStats();
}

async function updateCottageDetails(id) {
  const current = allCottages.find(c => Number(c.id) === Number(id));
  if (!current) return;
  updatingCottageId = id;
  document.getElementById('update-cottage-name').value = current.name;
  document.getElementById('update-cottage-price').value = current.price;
  document.getElementById('update-cottage-unit').value = current.pricing_unit;
  document.getElementById('update-cottage-amenities').value = current.amenities || '';
  document.getElementById('update-modal-error').style.display = 'none';
  document.getElementById('update-modal').classList.add('active');
}

function closeUpdateModal() {
  updatingCottageId = null;
  document.getElementById('update-modal').classList.remove('active');
}

async function submitCottageUpdate() {
  const current = allCottages.find(c => Number(c.id) === Number(updatingCottageId));
  if (!current) return;

  const data = {
    price: document.getElementById('update-cottage-price').value.trim(),
    pricing_unit: document.getElementById('update-cottage-unit').value.trim(),
    amenities: document.getElementById('update-cottage-amenities').value.trim()
  };
  const errorEl = document.getElementById('update-modal-error');

  if (!data.price || Number(data.price) <= 0) {
    errorEl.textContent = 'Please enter a valid price.';
    errorEl.style.display = 'block';
    return;
  }

  const fd = new FormData();
  fd.append('action', 'update');
  fd.append('id', String(updatingCottageId));
  fd.append('name', current.name);
  fd.append('description', current.description || '');
  fd.append('type', current.type);
  fd.append('capacity', String(current.capacity));
  fd.append('image_url', current.image_url || '');
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  fd.append('is_available', String(current.is_available ?? 1));

  const res = await fetch('cottages.php', { method: 'POST', body: fd });
  const json = await res.json();
  if (!json.success) {
    errorEl.textContent = json.message || 'Failed to update cottage';
    errorEl.style.display = 'block';
    return;
  }
  closeUpdateModal();
  loadCottages();
  loadStats();
}

function startOfDay(date) {
  const value = new Date(date);
  value.setHours(0, 0, 0, 0);
  return value;
}

function endOfDay(date) {
  const value = new Date(date);
  value.setHours(23, 59, 59, 999);
  return value;
}

function getBookingDate(booking) {
  const raw = booking.booked_on || booking.created_at || booking.check_in;
  return new Date(raw);
}

function formatReportDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function populateMonthOptions() {
  const select = document.getElementById('report-month-select');
  if (!select || select.options.length) return;

  const year = new Date().getFullYear();
  const months = Array.from({ length: 12 }, (_, index) => {
    const date = new Date(year, index, 1);
    return {
      value: String(index),
      label: date.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' })
    };
  });

  select.innerHTML = months.map(month => `
    <option value="${month.value}" ${Number(month.value) === selectedReportMonth ? 'selected' : ''}>${month.label}</option>
  `).join('');
}

function getReportRange(period) {
  const now = new Date();
  const today = startOfDay(now);
  let start = new Date(today);
  let end = endOfDay(now);
  let label = "Today's booking report";

  if (period === 'week') {
    const day = today.getDay();
    const diff = day === 0 ? 6 : day - 1;
    start.setDate(today.getDate() - diff);
    end = endOfDay(new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6));
    label = `Weekly report: ${formatReportDate(start)} - ${formatReportDate(end)}`;
  } else if (period === 'month') {
    start = new Date(today.getFullYear(), selectedReportMonth, 1);
    end = endOfDay(new Date(today.getFullYear(), selectedReportMonth + 1, 0));
    label = `Monthly report: ${start.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' })}`;
  }

  return { start, end, label };
}

function renderReportRows(bookings) {
  const tbody = document.getElementById('reports-tbody');
  if (!bookings.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">No bookings found for this report period</td></tr>';
    return;
  }

  tbody.innerHTML = bookings.map(b => `
    <tr>
      <td>${b.booking_id}</td>
      <td>
        <div style="font-weight:700">${b.customer_name}</div>
        <div style="font-size:0.75rem;color:#aaa">${b.customer_email}</div>
      </td>
      <td>
        <div style="font-weight:700">${b.cottage_name}</div>
        <div style="font-size:0.75rem;color:#aaa">${b.cottage_type}</div>
      </td>
      <td>${formatReportDate(b.booked_on || b.created_at)}</td>
      <td>${formatReportDate(b.check_in)} - ${formatReportDate(b.check_out)}</td>
      <td style="font-weight:700;color:var(--teal)">PHP ${Number(b.total_amount).toLocaleString()}</td>
      <td><span class="status-pill sp-${b.status}">${b.status}</span></td>
    </tr>
  `).join('');
}

function setReportPeriod(period, btn) {
  currentReportPeriod = period;
  document.querySelectorAll('#page-reports .filter-btn').forEach(button => button.classList.remove('active'));
  if (btn) btn.classList.add('active');
  const monthSelect = document.getElementById('report-month-select');
  if (monthSelect) {
    monthSelect.classList.toggle('show', period === 'month');
  }
  loadReports();
}

function changeReportMonth(value) {
  selectedReportMonth = Number(value);
  if (currentReportPeriod === 'month') loadReports();
}

async function loadReports() {
  await ensureBookingsLoaded();
  populateMonthOptions();
  const monthSelect = document.getElementById('report-month-select');
  if (monthSelect) {
    monthSelect.value = String(selectedReportMonth);
    monthSelect.classList.toggle('show', currentReportPeriod === 'month');
  }

  const { start, end, label } = getReportRange(currentReportPeriod);
  const filtered = allBookings.filter(booking => {
    const bookedDate = getBookingDate(booking);
    return bookedDate >= start && bookedDate <= end;
  });

  const total = filtered.length;
  const confirmed = filtered.filter(b => b.status === 'confirmed');
  const cancelled = filtered.filter(b => b.status === 'cancelled');
  const revenue = confirmed.reduce((sum, booking) => sum + Number(booking.total_amount || 0), 0);

  document.getElementById('report-period-label').textContent = label;
  document.getElementById('rep-total').textContent = total;
  document.getElementById('rep-revenue').textContent = 'PHP ' + revenue.toLocaleString();
  document.getElementById('rep-cancel-rate').textContent = total ? Math.round((cancelled.length / total) * 100) + '%' : '0%';
  document.getElementById('rep-confirm-rate').textContent = total ? Math.round((confirmed.length / total) * 100) + '%' : '0%';

  renderReportRows(filtered);
}

async function logout() {
  const fd = new FormData();
  fd.append('action','logout');
  await fetch('auth.php', {method:'POST',body:fd});
  window.location.href = 'index.php';
}

document.getElementById('update-modal').addEventListener('click', e => {
  if (e.target.id === 'update-modal') closeUpdateModal();
});

loadStats();
window.addEventListener('resize', renderCharts);
</script>
</body>
</html>


