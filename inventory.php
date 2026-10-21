<?php
require __DIR__ . '/includes/layout.php';

$cars = scan_cars('cars');

page_header('Inventory', 'inventory', ['description' => 'Current inventory of classic cars and trucks for sale.']);
?>
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark">For sale</p>
    <h1>Current inventory</h1>
  </div>
</section>

<section class="wrap list-section">
<?php if ($cars): ?>
  <div class="toolbar">
    <input id="filter" class="search" type="search" placeholder="Search by make, model, color..." aria-label="Search inventory">
    <span id="result-count" class="muted"><?= count($cars) ?> <?= count($cars) === 1 ? 'car' : 'cars' ?></span>
  </div>
<?php endif; ?>
  <?= render_grid($cars, 'No cars are listed right now. Check back soon, or <a href="contact.php">get in touch</a> about what we have coming in.') ?>
  <p id="no-results" class="empty" hidden>No cars match your search.</p>
</section>
<?php page_footer(); ?>
