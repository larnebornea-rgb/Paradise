<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paradise Resort & Cottages</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  :root {
    --teal: #009688;
    --teal-dark: #00796B;
    --teal-light: #4DB6AC;
    --gold: #FFC107;
    --white: #fff;
    --bg: #f5fafa;
    --text: #1a2e2e;
    --card-bg: #fff;
    --shadow: 0 4px 24px rgba(0,150,136,0.10);
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Lato',sans-serif; background:var(--bg); color:var(--text); }
  html { scroll-behavior:smooth; }
  nav {
    position:fixed; top:0; left:0; right:0; z-index:100;
    background:rgba(0,150,136,0.95);
    backdrop-filter:blur(10px);
    display:flex; align-items:center; justify-content:space-between;
    padding:0 40px; height:64px;
    box-shadow:0 2px 16px rgba(0,0,0,0.15);
  }
  .nav-brand { display:flex; align-items:center; gap:10px; color:#fff; text-decoration:none; }
  .nav-brand span { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:700; }
  .nav-links { display:flex; gap:20px; align-items:center }
  .nav-links a {
    color:rgba(255,255,255,0.9); text-decoration:none; font-size:0.9rem;
    font-weight:700; letter-spacing:0.5px; transition:color 0.2s;
  }
  .btn-nav {
    background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.5);
    padding:8px 20px; border-radius:30px; text-decoration:none;
    font-size:0.85rem; font-weight:700; transition:all 0.2s; cursor:pointer;
  }
  .btn-nav.primary { background:#fff; color:var(--teal); }
  .hero {
    height:100vh; min-height:600px;
    background:url('https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1600') center/cover no-repeat;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; position:relative;
  }
  .hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(0,77,64,0.65) 0%, rgba(0,150,136,0.45) 100%);
  }
  .hero-content { position:relative; z-index:2; padding:20px; }
  .hero h1 {
    font-family:'Playfair Display',serif;
    font-size:clamp(2.2rem, 6vw, 4.5rem);
    color:#fff; font-weight:700;
    text-shadow:0 2px 20px rgba(0,0,0,0.4);
    margin-bottom:16px; line-height:1.15;
  }
  .hero p { color:rgba(255,255,255,0.9); font-size:clamp(1rem,2vw,1.25rem); margin-bottom:36px; }
  .hero-btns { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; }
  .btn-hero {
    padding:14px 36px; border-radius:50px; font-size:1rem;
    font-weight:700; text-decoration:none; transition:all 0.25s; cursor:pointer; border:none;
  }
  .btn-hero.primary { background:var(--teal); color:#fff; }
  .btn-hero.outline { background:transparent; color:#fff; border:2px solid #fff; }
  section { padding:80px 40px; max-width:1200px; margin:0 auto; }
  .section-title { text-align:center; margin-bottom:50px; }
  .section-title h2 { font-family:'Playfair Display',serif; font-size:2.2rem; color:var(--teal-dark); }
  .features-grid {
    display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:28px;
  }
  .feature-card {
    background:var(--card-bg); border-radius:16px; padding:32px 24px;
    text-align:center; box-shadow:var(--shadow);
    border-top:4px solid var(--teal-light);
  }
  .feature-icon {
    font-size:2.2rem;
    line-height:1;
    margin-bottom:14px;
    display:block;
  }
  .cottages-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:28px;
  }
  .cottage-card {
    background:var(--card-bg); border-radius:18px; overflow:hidden;
    box-shadow:var(--shadow);
  }
  .cottage-card img { width:100%; height:200px; object-fit:cover; }
  .cottage-info { padding:18px 20px; }
  .cottage-price { font-size:1.15rem; font-weight:700; color:var(--teal); }
  .contact-section {
    background:linear-gradient(180deg, #27bcc4 0%, #1aa7b0 100%);
    padding:46px 40px ;
    margin-top:0;
  }
  .contact-panel {
    max-width:1160px;
    margin:0 auto;
    width:min(100%, 1160px);
    background:#d9f5f6;
    border-radius:24px;
    padding:28px 46px 22px;
    text-align:center;
    box-shadow:0 18px 50px rgba(0,0,0,0.12);
  }
  .contact-panel h2 {
    font-family:'Playfair Display',serif;
    font-size:2rem;
    color:#111;
    margin-bottom:18px;
  }
  .contact-grid {
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:10px;
    align-items:start;
  }
  .contact-item {
    padding:6px 10px 0;
    color:#111;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
  }
  .contact-icon {
    width:54px;
    height:54px;
    line-height:1;
    color:#4d5963;
    margin-bottom:14px;
    display:block;
  }
  .contact-item p {
    margin:0;
    font-size:0.82rem;
    line-height:1.45;
    font-family:'Playfair Display',serif;
  }
  footer {
    background:#143f42;
    color:rgba(255,255,255,0.92);
    text-align:center;
    padding:10px 20px 78px;
    font-size:0.9rem;
    margin-top:0;
  }
  .footer-title {
    font-family:'Playfair Display',serif;
    font-size:1rem;
    font-weight:700;
    margin-top: 20px;
    margin-bottom:4px;
    line-height:1.3;
  }
  .footer-subtitle {
    color:rgba(255,255,255,0.9);
    font-size:0.78rem;
  }
  .footer-content {
    max-width:1160px;
    margin:0 auto;
  }
  @media (max-width: 860px) {
    .contact-grid {
      grid-template-columns:1fr;
    }
    .contact-panel {
      padding:28px 24px 18px;
    }
    .footer-title {
      font-size:1.1rem;
    }
  }
  .modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.5); z-index:200;
    align-items:center; justify-content:center;
  }
  .modal-overlay.active { display:flex; }
  .modal {
    background:#fff; border-radius:20px; padding:40px;
    width:90%; max-width:420px; position:relative;
  }
  .modal-close {
    position:absolute; top:14px; right:18px; font-size:1.5rem;
    cursor:pointer; color:#aaa; background:none; border:none;
  }
  .form-group { margin-bottom:16px; }
  .form-group label { display:block; font-size:0.85rem; font-weight:700; color:#444; margin-bottom:6px; }
  .form-group input {
    width:100%; padding:12px 16px; border:1.5px solid #e0e0e0;
    border-radius:10px; font-size:0.95rem; outline:none;
  }
  .btn-submit {
    width:100%; padding:13px; background:var(--teal); color:#fff;
    border:none; border-radius:50px; font-size:1rem; font-weight:700;
    cursor:pointer; margin-top:8px;
  }
  .modal-switch { text-align:center; margin-top:16px; font-size:0.88rem; color:#666; }
  .modal-switch a { color:var(--teal); text-decoration:none; font-weight:700; cursor:pointer; }
  .error-msg { color:#e53935; font-size:0.85rem; margin-top:8px; text-align:center; display:none; }
  .success-msg { color:#43a047; font-size:0.85rem; margin-top:8px; text-align:center; display:none; }
</style>
</head>
<body>
<nav>
  <a class="nav-brand" href="index.php">
    <span>Paradise Resort & Cottages</span>
  </a>
  <div class="nav-links">
    <a href="#" onclick="openModal('register')">Create Account</a>
    <a href="#amenities">Amenities</a>
    <a href="#contact">Visit Us</a>
    <a href="#" class="btn-nav" onclick="openModal('login')">Sign In</a>
    <a href="#" class="btn-nav primary" onclick="openModal('register')">Book Now</a>
  </div>
</nav>

<div class="hero">
  <div class="hero-content">
    <h1>Paradise Resort & Cottages</h1>
    <p>Experience the pinnacle of luxury and relaxation at our oceanside cottages</p>
    <div class="hero-btns">
      <a href="#" class="btn-hero primary" onclick="openModal('login')">Book Your Stay</a>
      <a href="#" class="btn-hero outline" onclick="openModal('register')">Create Account</a>
    </div>
  </div>
</div>

<section id="amenities">
  <div class="section-title">
    <h2>Welcome to Paradise</h2>
    <p>Nestled along the pristine coastline, Paradise Resort offers an unforgettable escape with hand-crafted cottages providing the perfect sanctuary for couples, families, and groups.</p>
  </div>
  <div class="features-grid">
    <div class="feature-card"><span class="feature-icon">🏖️</span><h3>Beachfront Access</h3><p>Private beachfront with crystal-clear waters and sunset views.</p></div>
    <div class="feature-card"><span class="feature-icon">🌴</span><h3>Natural Beauty</h3><p>Surrounded by tropical gardens and scenic nature.</p></div>
    <div class="feature-card"><span class="feature-icon">🍽️</span><h3>Restaurant & Bar</h3><p>Local and international cuisine available.</p></div>
    <div class="feature-card"><span class="feature-icon">📶</span><h3>Modern Amenities</h3><p>WiFi and AC in selected cottages.</p></div>
  </div>
</section>

<section id="cottages">
  <div class="section-title">
    <h2>Our Cottages</h2>
    <p>Choose from our selection of beautiful cottage accommodations</p>
  </div>
  <div class="cottages-grid" id="cottages-container">
    <div style="text-align:center;padding:40px;color:#888;grid-column:1/-1;">Loading cottages...</div>
  </div>
</section>

<div class="contact-section" id="contact">
  <div class="contact-panel">
    <h2>Visit Us</h2>
    <div class="contact-grid">
      <div class="contact-item">
        <svg class="contact-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M32 57C32 57 48 42.5 48 27C48 18.1634 40.8366 11 32 11C23.1634 11 16 18.1634 16 27C16 42.5 32 57 32 57Z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="32" cy="27" r="8.5" stroke="currentColor" stroke-width="2.2"/>
        </svg>
        <p>123 Paradise Beach Road<br>Tropical Island, TI 12345</p>
      </div>
      <div class="contact-item">
        <svg class="contact-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M24.5 16.5C25.8 15.2 27.8 15.1 29.2 16.2L36 21.6C37.7 22.9 38.1 25.3 36.9 27L33.9 31.2C36.7 36.4 40.9 40.6 46.1 43.4L50.3 40.4C52 39.2 54.4 39.6 55.7 41.3L61.1 48.1C62.2 49.5 62.1 51.5 60.8 52.8L57.7 55.9C55.2 58.4 51.4 59.5 47.9 58.3C38.8 55.2 30.4 49.8 23.7 43.1C17 36.4 11.6 28 8.5 18.9C7.3 15.4 8.4 11.6 10.9 9.1L14 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M47 12C51.9706 12 56 16.0294 56 21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M47 5C55.8366 5 63 12.1634 63 21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        </svg>
        <p>+1 (555) 123-4567<br>Available 24/7</p>
      </div>
      <div class="contact-item">
        <svg class="contact-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="11" y="16" width="42" height="32" rx="6" stroke="currentColor" stroke-width="2.2"/>
          <path d="M14 21L29.3 31.9C31 33.1 33.3 33.1 35 31.9L50 21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <p>info@paradiseresort.com<br>reservations@paradiseresort.com</p>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="footer-content">
    <div class="footer-title">&copy; 2026 Paradise Resort & Cottages. All rights reserved.</div>
    <div class="footer-subtitle">Your tropical escape awaits</div>
  </div>
</footer>

<div class="modal-overlay" id="login-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('login')">&times;</button>
    <h2>Welcome Back</h2>
    <p>Sign in to your account</p>
    <div class="form-group">
      <label>Email</label>
      <input type="email" id="login-email" placeholder="you@example.com">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" id="login-password" placeholder="Password">
    </div>
    <div class="error-msg" id="login-error"></div>
    <button class="btn-submit" onclick="doLogin()">Sign In</button>
    <div class="modal-switch">Don't have an account? <a onclick="switchModal('login','register')">Register here</a></div>
  </div>
</div>

<div class="modal-overlay" id="register-modal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('register')">&times;</button>
    <h2>Join Paradise</h2>
    <p>Create an account to start booking your dream vacation</p>
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" id="reg-name" placeholder="John Doe">
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" id="reg-email" placeholder="you@example.com">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" id="reg-password" placeholder="Min. 6 characters">
    </div>
    <div class="form-group">
      <label>Confirm Password</label>
      <input type="password" id="reg-confirm" placeholder="Repeat password">
    </div>
    <div class="error-msg" id="reg-error"></div>
    <div class="success-msg" id="reg-success"></div>
    <button class="btn-submit" onclick="doRegister()">Create Account</button>
    <div class="modal-switch">Already have an account? <a onclick="switchModal('register','login')">Sign in here</a></div>
  </div>
</div>

<script>
function openModal(type) {
  document.getElementById(type+'-modal').classList.add('active');
}
function closeModal(type) {
  document.getElementById(type+'-modal').classList.remove('active');
}
function switchModal(from, to) {
  closeModal(from);
  openModal(to);
}

async function doLogin() {
  const email = document.getElementById('login-email').value;
  const password = document.getElementById('login-password').value;
  const errEl = document.getElementById('login-error');
  errEl.style.display='none';

  const fd = new FormData();
  fd.append('action','login');
  fd.append('email',email);
  fd.append('password',password);

  const res = await fetch('auth.php', {method:'POST', body:fd});
  const data = await res.json();

  if (data.success) {
    window.location.href = data.redirect;
  } else {
    errEl.textContent = data.message;
    errEl.style.display = 'block';
  }
}

async function doRegister() {
  const name = document.getElementById('reg-name').value;
  const email = document.getElementById('reg-email').value;
  const password = document.getElementById('reg-password').value;
  const confirm = document.getElementById('reg-confirm').value;
  const errEl = document.getElementById('reg-error');
  const sucEl = document.getElementById('reg-success');
  errEl.style.display='none';
  sucEl.style.display='none';

  const fd = new FormData();
  fd.append('action','register');
  fd.append('full_name',name);
  fd.append('email',email);
  fd.append('password',password);
  fd.append('confirm_password',confirm);

  const res = await fetch('auth.php', {method:'POST', body:fd});
  const data = await res.json();

  if (data.success) {
    sucEl.textContent = 'Account created! Redirecting to login...';
    sucEl.style.display = 'block';
    setTimeout(() => switchModal('register','login'), 1500);
  } else {
    errEl.textContent = data.message;
    errEl.style.display = 'block';
  }
}

async function loadCottages() {
  const res = await fetch('cottages.php?action=list');
  const data = await res.json();
  const container = document.getElementById('cottages-container');
  if (!data.success || !data.cottages.length) {
    container.innerHTML = '<p style="text-align:center;color:#888;grid-column:1/-1;">No cottages available</p>';
    return;
  }
  container.innerHTML = data.cottages.map(c => `
    <div class="cottage-card">
      <img src="${c.image_url}" alt="${c.name}" onerror="this.src='https://images.unsplash.com/photo-1470246973918-29a93221c455?w=1200'">
      <div class="cottage-info">
        <h3>${c.name}</h3>
        <p>${c.description}</p>
        <div>
          <span>Type: <b>${c.type}</b></span>
          <span style="margin-left:10px;">Capacity: <b>${c.capacity} guests</b></span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">
          <span class="cottage-price">₱${Number(c.price).toLocaleString()} <span style="font-size:0.75rem;color:#888">/${c.pricing_unit}</span></span>
          <button onclick="openModal('login')" style="background:var(--teal);color:#fff;border:none;padding:7px 18px;border-radius:30px;cursor:pointer;font-size:0.85rem;font-weight:700;">Book Now</button>
        </div>
      </div>
    </div>
  `).join('');
}

loadCottages();

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('active');
  });
});

document.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    if (document.getElementById('login-modal').classList.contains('active')) doLogin();
    if (document.getElementById('register-modal').classList.contains('active')) doRegister();
  }
});
</script>
</body>
</html>
