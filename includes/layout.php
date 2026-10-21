<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

const NAV = [
    'home' => ['index.php', 'Home'],
    'inventory' => ['inventory.php', 'Inventory'],
    'sold' => ['sold.php', 'Sold'],
    'about' => ['about.php', 'About'],
    'contact' => ['contact.php', 'Contact'],
];

function page_header(string $title, string $active = '', array $meta = []): void
{
    $s = site();
    $fullTitle = ($title === $s['name']) ? $title : $title . ' | ' . $s['name'];
    $desc = $meta['description'] ?? ($s['tagline'] ?? '');
    $cssVersion = (int)@filemtime(SITE_ROOT . '/assets/style.css');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($fullTitle) ?></title>
<?php if ($desc): ?><meta name="description" content="<?= h($desc) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= h($fullTitle) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= $cssVersion ?>">
</head>
<body>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="index.php"><?= h($s['name']) ?></a>
    <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
    <nav class="nav" aria-label="Main">
      <ul>
<?php foreach (NAV as $key => [$href, $label]): ?>
        <li><a href="<?= h($href) ?>"<?= $key === $active ? ' class="active" aria-current="page"' : '' ?>><?= h($label) ?></a></li>
<?php endforeach; ?>
      </ul>
<?php if ($s['phone']): ?>
      <a class="btn btn-primary btn-sm header-call" href="<?= h(phone_href($s['phone'])) ?>"><?= h($s['phone']) ?></a>
<?php endif; ?>
    </nav>
  </div>
</header>
<main id="main">
<?php
}

function page_footer(): void
{
    $s = site();
    ?></main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <p class="footer-brand"><?= h($s['name']) ?></p>
<?php if ($s['tagline']): ?>      <p class="muted-light"><?= h($s['tagline']) ?></p>
<?php endif; ?>
    </div>
    <div>
      <p class="footer-title">Explore</p>
      <ul>
<?php foreach (NAV as [$href, $label]): ?>
        <li><a href="<?= h($href) ?>"><?= h($label) ?></a></li>
<?php endforeach; ?>
      </ul>
    </div>
    <div>
      <p class="footer-title">Get in touch</p>
      <ul>
<?php if ($s['phone']): ?>        <li><a href="<?= h(phone_href($s['phone'])) ?>"><?= h($s['phone']) ?></a></li>
<?php endif; ?>
<?php if ($s['email']): ?>        <li><a href="mailto:<?= h($s['email']) ?>"><?= h($s['email']) ?></a></li>
<?php endif; ?>
<?php if ($s['address']): ?>        <li><?= h($s['address']) ?></li>
<?php endif; ?>
<?php foreach ($s['social'] as $label => $url): if (!$url) { continue; } ?>
        <li><a href="<?= h((string)$url) ?>" target="_blank" rel="noopener"><?= h((string)$label) ?></a></li>
<?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bottom">&copy; <?= date('Y') ?> <?= h($s['name']) ?></div>
</footer>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}

function render_card(array $car): string
{
    $cover = $car['images'][0] ?? null;
    $isSold = $car['type'] === 'sold';

    $chips = [];
    foreach ($car['specs'] as [$k, $v]) {
        if (!in_array(strtolower($k), ['price', 'status', 'year'], true) && count($chips) < 2) {
            $chips[] = $v;
        }
    }
    $search = mb_strtolower($car['name'] . ' ' . $car['short'] . ' ' . implode(' ', array_column($car['specs'], 1)));

    ob_start();
    ?>
<a class="card<?= $isSold ? ' is-sold' : '' ?>" href="<?= h(car_url($car)) ?>" data-search="<?= h($search) ?>">
  <div class="thumb">
<?php if ($cover): ?>    <img loading="lazy" src="<?= h(img_url($car, $cover)) ?>" alt="<?= h($car['name']) ?>">
<?php else: ?>    <div class="no-photo">Photo coming soon</div>
<?php endif; ?>
<?php if ($car['status']): ?>    <span class="badge<?= $isSold ? ' badge-dark' : '' ?>"><?= h($car['status']) ?></span>
<?php endif; ?>
  </div>
  <div class="card-body">
    <h3><?= h($car['name']) ?></h3>
<?php if ($chips): ?>    <p class="chips"><?= implode('', array_map(fn($c) => '<span>' . h($c) . '</span>', $chips)) ?></p>
<?php endif; ?>
<?php if ($car['short']): ?>    <p class="desc"><?= h($car['short']) ?></p>
<?php endif; ?>
    <div class="card-foot">
      <span class="price"><?= $car['price'] ? h($car['price']) : ($isSold ? '&nbsp;' : 'Call for price') ?></span>
      <span class="more">View details &rarr;</span>
    </div>
  </div>
</a>
<?php
    return (string)ob_get_clean();
}

function render_grid(array $cars, string $emptyMessage): string
{
    if (!$cars) {
        return '<p class="empty">' . $emptyMessage . '</p>';
    }
    return '<div class="grid">' . implode("\n", array_map('render_card', $cars)) . '</div>';
}
