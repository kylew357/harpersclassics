<?php
require __DIR__ . '/includes/layout.php';

$s = site();
$stock = scan_cars('cars');
$sold = scan_cars('sold');
$latest = array_slice($stock, 0, 3);
$about = about_paragraphs();
$hero = is_file(__DIR__ . '/assets/hero.jpg') ? 'assets/hero.jpg' : '';

page_header($s['name'], 'home', ['description' => $s['tagline']]);
?>
<section class="hero"<?= $hero ? ' style="--hero:url(\'' . h($hero) . '\')"' : '' ?>>
  <div class="wrap hero-inner">
<?php if ($s['since']): ?>    <p class="eyebrow">Since <?= h($s['since']) ?></p>
<?php endif; ?>
    <h1><?= h($s['headline']) ?></h1>
<?php if ($s['subheadline']): ?>    <p class="hero-sub"><?= h($s['subheadline']) ?></p>
<?php endif; ?>
    <div class="hero-actions">
      <a class="btn btn-primary" href="inventory.php">View inventory</a>
      <a class="btn btn-ghost-light" href="contact.php">Contact us</a>
    </div>
    <dl class="stats">
      <div><dt><?= count($stock) ?></dt><dd>Available now</dd></div>
      <div><dt><?= count($sold) ?></dt><dd>Sold</dd></div>
<?php if ($s['since']): ?>      <div><dt><?= max(0, (int)date('Y') - (int)$s['since']) ?>+</dt><dd>Years collecting</dd></div>
<?php endif; ?>
    </dl>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <div>
        <p class="eyebrow dark">Just arrived</p>
        <h2>Latest inventory</h2>
      </div>
      <a class="link-arrow" href="inventory.php">See all <?= count($stock) ?> &rarr;</a>
    </div>
    <?= render_grid($latest, 'New arrivals are on the way. Check back soon.') ?>
  </div>
</section>

<section class="section section-alt">
  <div class="wrap split">
    <div>
      <p class="eyebrow dark">Our story</p>
      <h2>Built on a love of old cars.</h2>
    </div>
    <div class="prose">
      <p><?= h($about[0]) ?></p>
      <a class="link-arrow" href="about.php">More about us &rarr;</a>
    </div>
  </div>
</section>

<section class="cta">
  <div class="wrap cta-inner">
    <h2>Looking for something specific?</h2>
    <p>Tell us what you're hunting for and we'll keep an eye out. We also buy and consign classics.</p>
    <a class="btn btn-light" href="contact.php">Get in touch</a>
  </div>
</section>
<?php page_footer(); ?>
