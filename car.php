<?php
require __DIR__ . '/includes/layout.php';

$s = site();
$type = (($_GET['type'] ?? 'cars') === 'sold') ? 'sold' : 'cars';
$car = find_car($type, (string)($_GET['car'] ?? ''));

if (!$car) {
    http_response_code(404);
    page_header('Not found', $type === 'sold' ? 'sold' : 'inventory');
    ?>
<section class="page-head">
  <div class="wrap">
    <h1>That car isn't here</h1>
    <p class="lead">It may have been sold or removed. <a href="inventory.php">Browse current inventory</a>.</p>
  </div>
</section>
<?php
    page_footer();
    exit;
}

$isSold = $type === 'sold';
$urls = array_map(fn($f) => img_url($car, $f), $car['images']);
$specs = array_filter($car['specs'], fn($row) => !in_array(strtolower($row[0]), ['price', 'status'], true));
$others = array_slice(array_values(array_filter(scan_cars('cars'), fn($c) => $c['name'] !== $car['name'])), 0, 3);
$mailto = 'mailto:' . $s['email'] . '?subject=' . rawurlencode('Inquiry: ' . $car['name']);

page_header($car['name'], $isSold ? 'sold' : 'inventory', ['description' => $car['short']]);
?>
<div class="wrap car-page">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?= $isSold ? 'sold.php' : 'inventory.php' ?>">&larr; <?= $isSold ? 'Sold' : 'Inventory' ?></a>
  </nav>

  <div class="car-head">
    <h1><?= h($car['name']) ?></h1>
<?php if ($car['status']): ?>    <span class="badge badge-static<?= $isSold ? ' badge-dark' : '' ?>"><?= h($car['status']) ?></span>
<?php endif; ?>
  </div>

  <div class="car-layout">
    <div>
<?php if ($urls): ?>
      <div class="gallery" data-gallery data-images="<?= h((string)json_encode($urls, JSON_UNESCAPED_SLASHES)) ?>">
        <div class="main-photo"><img src="<?= h($urls[0]) ?>" alt="<?= h($car['name']) ?>"></div>
<?php if (count($urls) > 1): ?>
        <div class="thumbs">
<?php foreach ($urls as $i => $u): ?>
          <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Photo <?= $i + 1 ?>"><img loading="lazy" src="<?= h($u) ?>" alt=""></button>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
<?php else: ?>
      <div class="main-photo"><div class="no-photo">Photos coming soon</div></div>
<?php endif; ?>

<?php if ($car['paragraphs']): ?>
      <div class="prose car-desc">
<?php foreach ($car['paragraphs'] as $p): ?>        <p><?= h($p) ?></p>
<?php endforeach; ?>
      </div>
<?php endif; ?>
    </div>

    <aside class="panel">
<?php if ($car['price']): ?>
      <p class="price-big"><?= h($car['price']) ?></p>
<?php endif; ?>
<?php if ($specs): ?>
      <table class="specs">
<?php foreach ($specs as [$k, $v]): ?>
        <tr><th><?= h($k) ?></th><td><?= h($v) ?></td></tr>
<?php endforeach; ?>
      </table>
<?php endif; ?>
<?php if (!$isSold): ?>
      <div class="panel-actions">
<?php if ($s['email']): ?>        <a class="btn btn-primary" href="<?= h($mailto) ?>">Inquire about this car</a>
<?php endif; ?>
<?php if ($s['phone']): ?>        <a class="btn btn-outline" href="<?= h(phone_href($s['phone'])) ?>">Call <?= h($s['phone']) ?></a>
<?php endif; ?>
      </div>
<?php else: ?>
      <p class="muted">This car has been sold. <a href="inventory.php">See what's available now.</a></p>
<?php endif; ?>
    </aside>
  </div>

<?php if ($others): ?>
  <section class="more-cars">
    <h2>More from our inventory</h2>
    <?= render_grid($others, '') ?>
  </section>
<?php endif; ?>
</div>
<?php page_footer(); ?>
