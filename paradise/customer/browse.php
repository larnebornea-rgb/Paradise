<?php
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../index.php');
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

<link rel="stylesheet" href="../assets/css/browse.css">
</head>

<body>

<header>
  <div class="header-brand">
    <h1>Paradise Resort</h1>
    <p>Welcome back, <?= htmlspecialchars($name) ?></p>
  </div>
  <button class="btn-logout" onclick="logout()">Logout</button>
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
      <div class="loading">Loading...</div>
    </div>
  </div>

  <div id="pane-mybooking" style="display:none;">
    <div id="bookings-container" class="mt">
      <div class="loading">Loading...</div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="booking-modal">
  <div class="modal">
    <h2 id="modal-cottage-name"></h2>
    <p>Select your check-in and check-out date</p>

    <div class="form-group">
      <label>Check-In Date</label>
      <input type="date" id="checkin-date">
    </div>

    <div class="form-group">
      <label>Check-Out Date</label>
      <input type="date" id="checkout-date">
    </div>

    <div class="total-box">
      <small>Total Amount</small>
      <strong id="total-display">₱0</strong>
    </div>

    <div class="error-msg" id="book-error"></div>

    <div class="modal-btns">
      <button class="btn-cancel-modal" onclick="closeBookingModal()">Cancel</button>
      <button class="btn-confirm" onclick="confirmBooking()">Confirm Booking</button>
    </div>
  </div>
</div>

<script src="../assets/js/browse.js"></script>

</body>
</html>
