<?php
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
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
<title>Paradise Resort - Customer Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  :root {
    --teal:#009688; --teal-dark:#00796B; --teal-light:#4DB6AC;
    --gold:#FFC107; --white:#fff; --bg:#f0f7f6; --text:#1a2e2e;
    --shadow:0 4px 24px rgba(0,150,136,0.10);
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Lato',sans-serif; background:var(--bg); color:var(--text); }
  header {
    background:linear-gradient(135deg, var(--teal-dark), var(--teal));
    color:#fff; padding:0 36px; height:70px;
    display:flex; align-items:center; justify-content:space-between;
    box-shadow:0 2px 16px rgba(0,0,0,0.15); position:sticky; top:0; z-index:100;
  }
  .header-brand h1 { font-family:'Playfair Display',serif; font-size:1.35rem; }
  .header-brand p { font-size:0.8rem; opacity:0.85; }
  .header-actions {
    display:flex;
    align-items:center;
    gap:12px;
  }
  .notification-wrap { position:relative; }
  .btn-notification {
    position:relative;
    width:46px;
    height:46px;
    border-radius:50%;
    border:1px solid rgba(255,255,255,0.35);
    background:rgba(255,255,255,0.14);
    color:#fff;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.08rem;
    transition:all 0.2s;
  }
  .btn-notification:hover { background:#fff; color:var(--teal); }
  .notification-count {
    position:absolute;
    top:-4px;
    right:-2px;
    min-width:20px;
    height:20px;
    padding:0 6px;
    border-radius:20px;
    background:#ff5252;
    color:#fff;
    font-size:0.72rem;
    font-weight:700;
    display:none;
    align-items:center;
    justify-content:center;
    box-shadow:0 6px 12px rgba(0,0,0,0.18);
  }
  .notification-count.show { display:flex; }
  .notification-panel {
    position:absolute;
    top:58px;
    right:0;
    width:340px;
    max-height:420px;
    overflow:auto;
    background:#fff;
    border-radius:18px;
    box-shadow:0 20px 45px rgba(0,0,0,0.18);
    padding:14px;
    display:none;
    color:var(--text);
  }
  .notification-panel.active { display:block; }
  .notification-panel h3 {
    font-family:'Playfair Display',serif;
    font-size:1.08rem;
    margin-bottom:4px;
    color:var(--teal-dark);
  }
  .notification-panel p {
    font-size:0.8rem;
    color:#7a8a8a;
    margin-bottom:12px;
  }
  .notification-item {
    background:#f8fbfb;
    border:1px solid #edf4f4;
    border-radius:14px;
    padding:12px;
    margin-bottom:10px;
  }
  .notification-item:last-child { margin-bottom:0; }
  .notification-top {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:6px;
  }
  .notification-title {
    font-size:0.88rem;
    font-weight:700;
    color:#194040;
  }
  .notification-meta {
    font-size:0.78rem;
    color:#6e7f7f;
    line-height:1.45;
  }
  .notification-empty {
    text-align:center;
    padding:18px 10px;
    color:#8ea1a1;
    font-size:0.84rem;
  }
  .btn-logout {
    display:flex; align-items:center; gap:8px;
    background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.4);
    padding:8px 20px; border-radius:30px; font-size:0.85rem; font-weight:700;
    cursor:pointer; transition:all 0.2s; text-decoration:none;
  }
  .btn-logout:hover { background:#fff; color:var(--teal); }
  .tabs-bar { max-width:1200px; margin:30px auto 0; padding:0 30px; }
  .tabs {
    display:inline-flex; gap:0; background:#fff;
    border-radius:50px; box-shadow:var(--shadow); overflow:hidden;
  }
  .tab-btn {
    padding:12px 32px; border:none; background:transparent;
    font-size:0.95rem; font-weight:700; cursor:pointer;
    color:#888; border-radius:50px; transition:all 0.25s;
  }
  .tab-btn.active { background:var(--teal); color:#fff; }
  .main { max-width:1200px; margin:0 auto; padding:24px 30px 60px; }
  .cottages-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(310px,1fr)); gap:24px;
    margin-top:24px;
  }
  .cottage-card {
    background:#fff; border-radius:18px; overflow:hidden;
    box-shadow:var(--shadow); transition:transform 0.25s;
  }
  .cottage-card:hover { transform:translateY(-6px); }
  .cottage-card img { width:100%; height:195px; object-fit:cover; }
  .cottage-info { padding:16px 18px 18px; }
  .cottage-info h3 { font-family:'Playfair Display',serif; font-size:1rem; }
  .cottage-info .desc { font-size:0.82rem; color:#888; margin:4px 0 10px; }
  .meta-row { display:flex; gap:12px; font-size:0.78rem; color:#666; margin-bottom:10px; }
  .meta-badge {
    background:#e0f2f1; color:var(--teal-dark); padding:2px 10px;
    border-radius:20px; font-size:0.75rem; font-weight:700;
  }
  .price-row { display:flex; align-items:center; justify-content:space-between; }
  .price { font-size:1.15rem; font-weight:700; color:var(--teal); }
  .unit { font-size:0.78rem; color:#aaa; }
  .amenities { font-size:0.76rem; color:#999; margin:8px 0; display:flex; flex-wrap:wrap; gap:4px; }
  .amenity-tag {
    background:#f5f5f5; padding:2px 8px; border-radius:10px; font-size:0.73rem;
  }
  .btn-book {
    background:var(--teal); color:#fff; border:none; padding:9px 20px;
    border-radius:30px; cursor:pointer; font-size:0.88rem; font-weight:700;
    transition:background 0.2s;
  }
  .btn-book:hover { background:var(--teal-dark); }
  .booking-card {
    background:#fff; border-radius:18px; overflow:hidden;
    box-shadow:var(--shadow); margin-bottom:20px;
  }
  .booking-header {
    background:
      linear-gradient(135deg, rgba(0, 121, 107, 0.88), rgba(0, 150, 136, 0.82)),
      var(--booking-bg, url('https://images.unsplash.com/photo-1470246973918-29a93221c455?w=1200'));
    background-size:cover;
    background-position:center;
    color:#fff; padding:16px 24px;
    display:flex; align-items:center; justify-content:space-between;
  }
  .booking-header h3 { font-family:'Playfair Display',serif; font-size:1.1rem; }
  .booking-header small { opacity:0.8; font-size:0.8rem; }
  .status-badge {
    padding:4px 14px; border-radius:20px; font-size:0.8rem; font-weight:700;
  }
  .status-pending { background:#FFC107; color:#333; }
  .status-confirmed { background:#4CAF50; color:#fff; }
  .status-cancelled { background:#f44336; color:#fff; }
  .booking-body { padding:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; }
  .booking-body.with-bg {
    position:relative;
    overflow:hidden;
    background:transparent;
  }
  .booking-body.with-bg::before {
    content:'';
    position:absolute;
    inset:0;
    background-image:var(--booking-body-bg, url('https://images.unsplash.com/photo-1470246973918-29a93221c455?w=1200'));
    background-size:cover;
    background-position:center;
    filter:blur(4px);
    transform:scale(1.06);
    opacity:0.6;
    z-index:0;
  }
  .booking-body.with-bg::after {
    content:'';
    position:absolute;
    inset:0;
    background:rgba(255,255,255,0.42);
    z-index:0;
  }
  .booking-body.with-bg > * {
    position:relative;
    z-index:1;
  }
  .date-info { display:flex; flex-direction:column; gap:12px; }
  .date-item { display:flex; align-items:center; gap:12px; }
  .date-icon { font-size:1.3rem; }
  .date-label { font-size:0.78rem; color:#888; }
  .date-value { font-size:1.05rem; font-weight:700; }
  .amount-box {
    background:linear-gradient(135deg, #e0f2f1, #b2dfdb);
    border-radius:12px; padding:20px 28px; text-align:center; min-width:200px;
  }
  .amount-label { font-size:0.8rem; color:#555; margin-bottom:6px; }
  .amount-value { font-size:2rem; font-weight:700; color:var(--teal-dark); }
  .amount-meta { font-size:0.78rem; color:#666; margin-top:8px; }
  .btn-cancel {
    background:#f44336; color:#fff; border:none; width:100%;
    padding:11px; border-radius:30px; cursor:pointer; font-size:0.9rem;
    font-weight:700; margin-top:14px; transition:background 0.2s;
  }
  .btn-cancel:hover { background:#d32f2f; }
  .modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.55); z-index:300;
    align-items:center; justify-content:center;
  }
  .modal-overlay.active { display:flex; }
  .modal {
    background:#fff; border-radius:20px; padding:34px;
    width:90%; max-width:380px; position:relative;
    animation:modalIn 0.3s ease;
  }
  @keyframes modalIn { from{opacity:0;transform:scale(0.9)} to{opacity:1;transform:scale(1)} }
  .modal h2 { font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--teal-dark); margin-bottom:4px; }
  .modal p { color:#888; font-size:0.85rem; margin-bottom:20px; }
  .form-group { margin-bottom:14px; }
  .form-group label { display:block; font-size:0.83rem; font-weight:700; color:#555; margin-bottom:5px; }
  .form-group input[type=date] {
    width:100%; padding:11px 14px; border:1.5px solid #ddd;
    border-radius:10px; font-size:0.95rem; outline:none;
  }
  .form-group input[type=date]:focus { border-color:var(--teal); }
  .availability-pill {
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-top:12px;
    padding:10px 14px;
    border-radius:999px;
    font-size:0.82rem;
    font-weight:700;
    background:#edf8f7;
    color:var(--teal-dark);
  }
  .availability-pill.unavailable {
    background:#ffebee;
    color:#c62828;
  }
  .availability-pill.available {
    background:#e8f5e9;
    color:#2e7d32;
  }
  .total-box {
    background:#e0f2f1; border-radius:10px; padding:14px;
    text-align:center; margin:16px 0;
  }
  .total-box small { color:#555; font-size:0.8rem; }
  .total-box strong { display:block; font-size:1.5rem; color:var(--teal-dark); font-weight:700; }
  .modal-btns { display:flex; gap:10px; margin-top:8px; }
  .btn-cancel-modal {
    flex:1; padding:11px; border-radius:30px; border:1.5px solid #ddd;
    background:#fff; color:#888; cursor:pointer; font-size:0.9rem; font-weight:700;
  }
  .btn-confirm {
    flex:2; padding:11px; border-radius:30px; border:none;
    background:var(--teal); color:#fff; cursor:pointer; font-size:0.9rem; font-weight:700;
    opacity:1;
    box-shadow:none;
    transition:background 0.2s, opacity 0.2s;
  }
  .btn-confirm:hover { background:var(--teal-dark); }
  .btn-confirm.enabled {
    background:var(--teal);
    color:#fff;
    opacity:1;
  }
  .btn-confirm:disabled {
    background:#b8d7d4;
    color:#f7fbfb;
    cursor:not-allowed;
    opacity:0.85;
  }
  .error-msg { color:#e53935; font-size:0.82rem; text-align:center; margin-top:8px; display:none; }
  .empty-state { text-align:center; padding:60px 20px; color:#aaa; }
  .empty-state .emoji { font-size:3rem; }
</style>
</head>
<body>
<header>
  <div class="header-brand">
    <h1>Paradise Resort</h1>
    <p>Welcome back, <?= htmlspecialchars($name) ?></p>
  </div>
  <div class="header-actions">
    <div class="notification-wrap">
      <button class="btn-notification" type="button" onclick="toggleNotifications()" aria-label="View booking notifications">
        <span>&#128276;</span>
        <span class="notification-count" id="notification-count">0</span>
      </button>
      <div class="notification-panel" id="notification-panel">
        <h3>Booking Notifications</h3>
        <p>Confirmed and cancelled booking updates will appear here.</p>
        <div id="notification-list">
          <div class="notification-empty">Loading notifications...</div>
        </div>
      </div>
    </div>
    <button class="btn-logout" onclick="logout()">Logout</button>
  </div>
</header>

<div class="tabs-bar">
  <div class="tabs">
    <button class="tab-btn active" id="tab-browse" onclick="showTab('browse')">Browse Cottages</button>
    <button class="tab-btn" id="tab-mybooking" onclick="showTab('mybooking')">My Booking</button>
  </div>
</div>

<div class="main">
  <div id="pane-browse">
    <div class="cottages-grid" id="cottages-grid">
      <div style="grid-column:1/-1;text-align:center;color:#aaa;padding:40px;">Loading...</div>
    </div>
  </div>

  <div id="pane-mybooking" style="display:none;">
    <div id="bookings-container" style="margin-top:24px;">
      <div style="text-align:center;color:#aaa;padding:40px;">Loading...</div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="booking-modal">
  <div class="modal">
    <h2 id="modal-cottage-name">Cottage Name</h2>
    <p>Select your check-in and check-out date</p>
    <div class="form-group">
      <label>Check-In Date</label>
      <input type="date" id="checkin-date">
    </div>
    <div class="form-group">
      <label>Check-Out Date</label>
      <input type="date" id="checkout-date">
    </div>
    <div class="availability-pill" id="availability-status">Checking selected dates...</div>
    <div class="total-box">
      <small>Total Amount</small>
      <strong id="total-display">0</strong>
    </div>
    <div class="error-msg" id="book-error"></div>
    <div class="modal-btns">
      <button class="btn-cancel-modal" onclick="closeBookingModal()">Cancel</button>
      <button class="btn-confirm" id="confirm-booking-btn" onclick="confirmBooking()">Confirm Booking</button>
    </div>
  </div>
</div>

<script>
let currentCottage = null;
let allMyBookings = [];
const today = new Date().toISOString().split('T')[0];
let notificationRefreshTimer = null;
let selectedDatesAvailable = true;

function getViewedNotificationKeys() {
  try {
    return JSON.parse(localStorage.getItem('paradise_viewed_notifications') || '[]');
  } catch (error) {
    return [];
  }
}

function setViewedNotificationKeys(keys) {
  localStorage.setItem('paradise_viewed_notifications', JSON.stringify(keys));
}

function getNotificationKey(booking) {
  return `${booking.booking_id}:${booking.status}`;
}

function markNotificationsAsViewed(bookings) {
  const viewed = new Set(getViewedNotificationKeys());
  bookings
    .filter(b => b.status === 'confirmed' || b.status === 'cancelled')
    .forEach(b => viewed.add(getNotificationKey(b)));
  setViewedNotificationKeys(Array.from(viewed));
}

function showTab(tab) {
  document.getElementById('pane-browse').style.display = tab==='browse' ? '' : 'none';
  document.getElementById('pane-mybooking').style.display = tab==='mybooking' ? '' : 'none';
  document.getElementById('tab-browse').classList.toggle('active', tab==='browse');
  document.getElementById('tab-mybooking').classList.toggle('active', tab==='mybooking');
  if (tab === 'mybooking') loadMyBookings();
}

async function loadCottages() {
  const res = await fetch('cottages.php?action=list');
  const data = await res.json();
  const grid = document.getElementById('cottages-grid');
  if (!data.success || !data.cottages.length) {
    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#aaa;">No cottages available</p>';
    return;
  }
  grid.innerHTML = data.cottages.map(c => {
    const amenities = c.amenities ? c.amenities.split(',').map(a =>
      `<span class="amenity-tag">${a.trim()}</span>`).join('') : '';
    return `
    <div class="cottage-card">
      <img src="${c.image_url}" alt="${c.name}" onerror="this.src='https://images.unsplash.com/photo-1470246973918-29a93221c455?w=1200'">
      <div class="cottage-info">
        <h3>${c.name}</h3>
        <p class="desc">${c.description}</p>
        <div class="meta-row">
          <span>Type: <span class="meta-badge">${c.type}</span></span>
          <span>Capacity: <b>${c.capacity} guests</b></span>
        </div>
        <div class="amenities">${amenities}</div>
        <div class="price-row">
          <span class="price">₱${Number(c.price).toLocaleString()} <span class="unit">/${c.pricing_unit}</span></span>
          <button class="btn-book" onclick='openBooking(${JSON.stringify(c)})'>Book Now</button>
        </div>
      </div>
    </div>`;
  }).join('');
}

function openBooking(cottage) {
  currentCottage = cottage;
  selectedDatesAvailable = true;
  const confirmBtn = document.getElementById('confirm-booking-btn');
  confirmBtn.disabled = true;
  confirmBtn.classList.remove('enabled');
  document.getElementById('modal-cottage-name').textContent = cottage.name;
  document.getElementById('checkin-date').value = today;
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate()+1);
  document.getElementById('checkout-date').value = tomorrow.toISOString().split('T')[0];
  document.getElementById('checkin-date').min = today;
  document.getElementById('checkout-date').min = tomorrow.toISOString().split('T')[0];
  document.getElementById('book-error').style.display = 'none';
  calcTotal();
  checkSelectedDateAvailability();
  document.getElementById('booking-modal').classList.add('active');
}

function closeBookingModal() {
  document.getElementById('booking-modal').classList.remove('active');
}

function calcTotal() {
  if (!currentCottage) return;
  const inDate = new Date(document.getElementById('checkin-date').value);
  const outDate = new Date(document.getElementById('checkout-date').value);
  const days = Math.max(0, Math.round((outDate - inDate) / 86400000));
  const total = days * Number(currentCottage.price);
  document.getElementById('total-display').textContent = `₱${total.toLocaleString()} (${days} ${currentCottage.pricing_unit}${days!==1?'s':''} x ₱${Number(currentCottage.price).toLocaleString()})`;
}

function setAvailabilityStatus(available, message) {
  const statusEl = document.getElementById('availability-status');
  const confirmBtn = document.getElementById('confirm-booking-btn');
  selectedDatesAvailable = available;
  statusEl.textContent = message;
  statusEl.classList.remove('available', 'unavailable');
  statusEl.classList.add(available ? 'available' : 'unavailable');
  confirmBtn.disabled = !available;
  confirmBtn.classList.toggle('enabled', available);
}

async function checkSelectedDateAvailability() {
  if (!currentCottage) return;

  const checkin = document.getElementById('checkin-date').value;
  const checkout = document.getElementById('checkout-date').value;

  if (!checkin || !checkout) {
    setAvailabilityStatus(false, 'Please select both dates');
    return;
  }

  const statusEl = document.getElementById('availability-status');
  const confirmBtn = document.getElementById('confirm-booking-btn');
  statusEl.textContent = 'Checking availability...';
  statusEl.classList.remove('available', 'unavailable');
  confirmBtn.disabled = true;
  confirmBtn.classList.remove('enabled');

  const fd = new FormData();
  fd.append('action', 'availability');
  fd.append('cottage_id', currentCottage.id);
  fd.append('check_in', checkin);
  fd.append('check_out', checkout);

  const res = await fetch('reservations.php', {method:'POST', body:fd});
  const data = await res.json();

  if (!data.success) {
    setAvailabilityStatus(false, data.message || 'Unable to check availability');
    return;
  }

  setAvailabilityStatus(
    data.available,
    data.available ? 'Selected dates are available' : 'Selected dates are unavailable'
  );
}

document.getElementById('checkin-date').addEventListener('change', () => {
  const inVal = document.getElementById('checkin-date').value;
  const nextDay = new Date(inVal);
  nextDay.setDate(nextDay.getDate()+1);
  document.getElementById('checkout-date').min = nextDay.toISOString().split('T')[0];
  if (document.getElementById('checkout-date').value <= inVal) {
    document.getElementById('checkout-date').value = nextDay.toISOString().split('T')[0];
  }
  calcTotal();
  checkSelectedDateAvailability();
});
document.getElementById('checkout-date').addEventListener('change', () => {
  calcTotal();
  checkSelectedDateAvailability();
});

async function confirmBooking() {
  const errEl = document.getElementById('book-error');
  errEl.style.display = 'none';
  const checkin = document.getElementById('checkin-date').value;
  const checkout = document.getElementById('checkout-date').value;

  await checkSelectedDateAvailability();
  if (!selectedDatesAvailable) {
    errEl.textContent = 'Selected dates are unavailable';
    errEl.style.display = 'block';
    return;
  }

  const fd = new FormData();
  fd.append('action','book');
  fd.append('cottage_id', currentCottage.id);
  fd.append('check_in', checkin);
  fd.append('check_out', checkout);

  const res = await fetch('reservations.php', {method:'POST', body:fd});
  const data = await res.json();

  if (data.success) {
    closeBookingModal();
    await loadNotifications();
    showTab('mybooking');
  } else {
    errEl.textContent = data.message || 'Booking failed';
    errEl.style.display = 'block';
  }
}

async function fetchMyBookings() {
  const fd = new FormData();
  fd.append('action','my_bookings');
  const res = await fetch('reservations.php', {method:'POST', body:fd});
  const data = await res.json();
  return data.success ? data.bookings : [];
}

function renderNotifications(bookings) {
  const list = document.getElementById('notification-list');
  const updates = bookings.filter(b => b.status === 'confirmed' || b.status === 'cancelled');
  const counter = document.getElementById('notification-count');
  const viewed = new Set(getViewedNotificationKeys());
  const unreadCount = updates.filter(b => !viewed.has(getNotificationKey(b))).length;

  counter.textContent = unreadCount;
  counter.classList.toggle('show', unreadCount > 0);

  if (!updates.length) {
    list.innerHTML = '<div class="notification-empty">No booking updates yet.</div>';
    return;
  }

  list.innerHTML = updates
    .sort((a, b) => new Date(b.booked_on || b.created_at) - new Date(a.booked_on || a.created_at))
    .map(b => `
      <div class="notification-item">
        <div class="notification-top">
          <div class="notification-title">${b.cottage_name}</div>
          <span class="status-badge status-${b.status}">${b.status.charAt(0).toUpperCase() + b.status.slice(1)}</span>
        </div>
        <div class="notification-meta">
          Booking ID: ${b.booking_id}<br>
          Check-in: ${formatDate(b.check_in)}<br>
          Check-out: ${formatDate(b.check_out)}
        </div>
      </div>
    `).join('');
}

async function loadNotifications() {
  allMyBookings = await fetchMyBookings();
  renderNotifications(allMyBookings);
}

function startNotificationAutoRefresh() {
  if (notificationRefreshTimer) clearInterval(notificationRefreshTimer);
  notificationRefreshTimer = setInterval(() => {
    loadNotifications();
  }, 10000);
}

async function loadMyBookings() {
  const container = document.getElementById('bookings-container');
  container.innerHTML = '<div style="text-align:center;color:#aaa;padding:40px;">Loading...</div>';

  allMyBookings = await fetchMyBookings();
  renderNotifications(allMyBookings);

  if (!allMyBookings.length) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="emoji">🏝️</div>
        <p style="margin-top:16px;font-size:1.05rem;">No bookings yet</p>
        <p style="margin-top:8px;">Start exploring our cottages and book your paradise getaway!</p>
      </div>`;
    return;
  }

  container.innerHTML = allMyBookings.map(b => `
    <div class="booking-card">
      <div class="booking-header" style="--booking-bg:url('${(b.image_url || 'https://images.unsplash.com/photo-1470246973918-29a93221c455?w=1200').replace(/'/g, '%27')}')">
        <div>
          <h3>${b.cottage_name}</h3>
          <small>Booking ID: ${b.booking_id}</small>
        </div>
        <span class="status-badge status-${b.status}">${b.status.charAt(0).toUpperCase()+b.status.slice(1)}</span>
      </div>
      <div class="booking-body with-bg" style="--booking-body-bg:url('${(b.image_url || 'https://images.unsplash.com/photo-1470246973918-29a93221c455?w=1200').replace(/'/g, '%27')}')">
        <div class="date-info">
          <div class="date-item">
            <span class="date-icon">📅</span>
            <div><div class="date-label">Check-in</div><div class="date-value">${formatDate(b.check_in)}</div></div>
          </div>
          <div class="date-item">
            <span class="date-icon">📅</span>
            <div><div class="date-label">Check-out</div><div class="date-value">${formatDate(b.check_out)}</div></div>
          </div>
        </div>
        <div class="amount-box">
          <div class="amount-label">Total Amount</div>
          <div class="amount-value">₱${Number(b.total_amount).toLocaleString()}</div>
          <div class="amount-meta">
            Cottage Type: ${b.cottage_type}<br>
            Booked on: ${formatDate(b.booked_on)}
          </div>
          ${b.status !== 'cancelled' ? `<button class="btn-cancel" onclick="cancelBooking('${b.booking_id}')">Cancel Booking</button>` : ''}
        </div>
      </div>
    </div>
  `).join('');
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', {day:'2-digit',month:'2-digit',year:'numeric'});
}

async function cancelBooking(bookingId) {
  if (!confirm('Cancel this booking?')) return;
  const fd = new FormData();
  fd.append('action','cancel');
  fd.append('booking_id', bookingId);
  const res = await fetch('reservations.php', {method:'POST', body:fd});
  const data = await res.json();
  if (data.success) {
    await loadNotifications();
    loadMyBookings();
  }
  else alert(data.message || 'Could not cancel');
}

function toggleNotifications() {
  const panel = document.getElementById('notification-panel');
  const willOpen = !panel.classList.contains('active');
  panel.classList.toggle('active');
  if (willOpen) {
    markNotificationsAsViewed(allMyBookings);
    renderNotifications(allMyBookings);
  }
}

async function logout() {
  const fd = new FormData();
  fd.append('action','logout');
  await fetch('auth.php', {method:'POST', body:fd});
  window.location.href = 'index.php';
}

document.getElementById('booking-modal').addEventListener('click', e => {
  if (e.target === document.getElementById('booking-modal')) closeBookingModal();
});

document.addEventListener('click', e => {
  const wrap = document.querySelector('.notification-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('notification-panel').classList.remove('active');
  }
});

loadCottages();
loadNotifications();
startNotificationAutoRefresh();
</script>
</body>
</html>
