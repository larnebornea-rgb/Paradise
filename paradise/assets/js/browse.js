let currentCottage = null;
const today = new Date().toISOString().split('T')[0];

document.addEventListener("DOMContentLoaded", () => {
  loadCottages();

  document.getElementById('checkin-date').addEventListener('change', handleDateChange);
  document.getElementById('checkout-date').addEventListener('change', calcTotal);

  document.getElementById('booking-modal').addEventListener('click', e => {
    if (e.target.id === 'booking-modal') closeBookingModal();
  });
});

function showTab(tab) {
  document.getElementById('pane-browse').style.display = tab === 'browse' ? '' : 'none';
  document.getElementById('pane-mybooking').style.display = tab === 'mybooking' ? '' : 'none';

  document.getElementById('tab-browse').classList.toggle('active', tab === 'browse');
  document.getElementById('tab-mybooking').classList.toggle('active', tab === 'mybooking');

  if (tab === 'mybooking') loadMyBookings();
}

async function loadCottages() {
  const res = await fetch('../cottages.php?action=list');
  const data = await res.json();
  const grid = document.getElementById('cottages-grid');

  if (!data.success || !data.cottages.length) {
    grid.innerHTML = '<p class="loading">No cottages available</p>';
    return;
  }

  grid.innerHTML = data.cottages.map(c => {
    const amenities = c.amenities
      ? c.amenities.split(',').map(a => `<span class="amenity-tag">${a.trim()}</span>`).join('')
      : '';

    return `
    <div class="cottage-card">
      <img src="${c.image_url}" alt="${c.name}">
      <div class="cottage-info">
        <h3>${c.name}</h3>
        <p class="desc">${c.description}</p>

        <div class="meta-row">
          <span>${c.type}</span>
          <span>${c.capacity} guests</span>
        </div>

        <div class="amenities">${amenities}</div>

        <div class="price-row">
          <span class="price">₱${Number(c.price).toLocaleString()}</span>
          <button class="btn-book" onclick='openBooking(${JSON.stringify(c)})'>Book</button>
        </div>
      </div>
    </div>`;
  }).join('');
}

function openBooking(cottage) {
  currentCottage = cottage;

  document.getElementById('modal-cottage-name').textContent = cottage.name;
  document.getElementById('checkin-date').value = today;

  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);

  document.getElementById('checkout-date').value = tomorrow.toISOString().split('T')[0];
  document.getElementById('checkin-date').min = today;
  document.getElementById('checkout-date').min = tomorrow.toISOString().split('T')[0];

  calcTotal();
  document.getElementById('book-error').style.display = 'none';
  document.getElementById('booking-modal').classList.add('active');
}

function closeBookingModal() {
  document.getElementById('booking-modal').classList.remove('active');
}

function handleDateChange() {
  const inVal = document.getElementById('checkin-date').value;
  const nextDay = new Date(inVal);
  nextDay.setDate(nextDay.getDate() + 1);

  document.getElementById('checkout-date').min = nextDay.toISOString().split('T')[0];
  if (document.getElementById('checkout-date').value <= inVal) {
    document.getElementById('checkout-date').value = nextDay.toISOString().split('T')[0];
  }

  calcTotal();
}

function calcTotal() {
  if (!currentCottage) return;

  const inDate = new Date(document.getElementById('checkin-date').value);
  const outDate = new Date(document.getElementById('checkout-date').value);

  const days = Math.max(0, Math.round((outDate - inDate) / 86400000));
  const total = days * Number(currentCottage.price);

  document.getElementById('total-display').textContent =
    `₱${total.toLocaleString()} (${days} ${currentCottage.pricing_unit}${days !== 1 ? 's' : ''})`;
}

async function confirmBooking() {
  const fd = new FormData();
  fd.append('action', 'book');
  fd.append('cottage_id', currentCottage.id);
  fd.append('check_in', document.getElementById('checkin-date').value);
  fd.append('check_out', document.getElementById('checkout-date').value);

  const res = await fetch('../reservations.php', {
    method: 'POST',
    body: fd
  });

  const data = await res.json();

  if (data.success) {
    closeBookingModal();
    showTab('mybooking');
  } else {
    document.getElementById('book-error').textContent = data.message || 'Booking failed';
    document.getElementById('book-error').style.display = 'block';
  }
}

async function loadMyBookings() {
  const container = document.getElementById('bookings-container');

  const fd = new FormData();
  fd.append('action', 'my_bookings');

  const res = await fetch('../reservations.php', {
    method: 'POST',
    body: fd
  });

  const data = await res.json();

  if (!data.success || !data.bookings.length) {
    container.innerHTML = '<p class="loading">No bookings yet</p>';
    return;
  }

  container.innerHTML = data.bookings.map(b => `
    <div class="booking-card">
      <h3>${b.cottage_name}</h3>
      <p>${b.check_in} → ${b.check_out}</p>
      <p>₱${Number(b.total_amount).toLocaleString()}</p>
      ${b.status !== 'cancelled'
        ? `<button onclick="cancelBooking('${b.booking_id}')">Cancel</button>`
        : ''}
    </div>
  `).join('');
}

async function cancelBooking(id) {
  if (!confirm('Cancel booking?')) return;

  const fd = new FormData();
  fd.append('action', 'cancel');
  fd.append('booking_id', id);

  await fetch('../reservations.php', {
    method: 'POST',
    body: fd
  });

  loadMyBookings();
}

async function logout() {
  const fd = new FormData();
  fd.append('action', 'logout');

  await fetch('../auth.php', {
    method: 'POST',
    body: fd
  });

  window.location.href = '../index.php';
}
