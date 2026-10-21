<?php
require __DIR__ . '/includes/layout.php';

$cars = scan_cars('sold');

page_header('Sold', 'sold', ['description' => 'A look at classic cars and trucks we have recently sold.']);
?>
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark">Past sales</p>
    <h1>Sold</h1>
    <p class="lead">A look at some of the cars that have found new homes.</p>
  </div>
</section>

<section class="wrap list-section">
  <?= render_grid($cars, 'Nothing here yet.') ?>
</section>
<?php page_footer(); ?>
