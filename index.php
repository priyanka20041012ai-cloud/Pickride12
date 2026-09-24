<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$vehicles = $pdo->query("SELECT * FROM vehicle_types WHERE is_active = 1 ORDER BY base_fare ASC")->fetchAll();

$pageTitle = 'Book a ride';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container hero-grid">
    <div>
      <h1>Your ride across town, without the wait.</h1>
      <p class="lede">Tuks, cars and vans on demand. Enter where you're starting and where you're headed — see the fare before you book, every time.</p>
      <div class="hero-actions">
        <?php if (is_logged_in()): ?>
          <a href="<?= BASE_URL ?>/book.php" class="btn btn-primary">Book a ride</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Get started</a>
          <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Log in</a>
        <?php endif; ?>
      </div>
    </div>
    <div>
      <svg class="route-line" viewBox="0 0 420 260" xmlns="http://www.w3.org/2000/svg">
        <path d="M40,210 C120,210 90,90 190,90 C280,90 260,40 380,40" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2.5" stroke-dasharray="1 10" stroke-linecap="round"/>
        <circle cx="40" cy="210" r="8" fill="#fff" stroke="#F2A83B" stroke-width="3"/>
        <circle cx="380" cy="40" r="8" fill="#F2A83B" stroke="#fff" stroke-width="3"/>
        <text x="52" y="230" fill="rgba(255,255,255,0.65)" font-family="Inter, sans-serif" font-size="13">Pickup</text>
        <text x="315" y="66" fill="rgba(255,255,255,0.65)" font-family="Inter, sans-serif" font-size="13">Destination</text>
      </svg>
    </div>
  </div>
</section>

<section class="section" id="how-it-works">
  <div class="container">
    <div class="section-head">
      <h2>Three steps, then you're moving</h2>
      <p>No app install required — book straight from your browser.</p>
    </div>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-num">01</div>
        <h3>Set your route</h3>
        <p>Tell us your pickup point and destination, and pick the vehicle that suits your trip.</p>
      </div>
      <div class="step-card">
        <div class="step-num">02</div>
        <h3>See the fare upfront</h3>
        <p>The estimated fare is calculated from distance before you confirm — no surprises.</p>
      </div>
      <div class="step-card">
        <div class="step-num">03</div>
        <h3>Track your trip</h3>
        <p>Follow your ride's status from "looking for a driver" through to drop-off.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="vehicles" style="background:var(--paper-raised); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
  <div class="container">
    <div class="section-head">
      <h2>Pick the ride that fits</h2>
      <p>Every fare is base charge plus a per-kilometre rate, shown before you book.</p>
    </div>
    <div class="vehicle-showcase">
      <?php foreach ($vehicles as $v): ?>
        <div class="vehicle-tile">
          <h3><?= e($v['name']) ?></h3>
          <p><?= e($v['description']) ?></p>
          <div class="price">LKR <?= number_format($v['base_fare'], 0) ?> <span style="font-size:0.8rem; font-weight:400; color:var(--ink-600);">+ <?= number_format($v['rate_per_km'], 0) ?>/km</span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
