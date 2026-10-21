<?php
require __DIR__ . '/includes/layout.php';

$s = site();
$map = $s['address'] ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($s['address']) : '';

page_header('Contact', 'contact', ['description' => 'Get in touch about a car, or to buy or consign a classic.']);
?>
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark">Say hello</p>
    <h1>Contact us</h1>
    <p class="lead">Questions about a car, want to schedule a visit, or have something to sell or consign? Reach out.</p>
  </div>
</section>

<section class="wrap list-section">
  <div class="contact-grid">
<?php if ($s['phone']): ?>
    <div class="contact-card">
      <p class="footer-title dark">Call or text</p>
      <a class="contact-big" href="<?= h(phone_href($s['phone'])) ?>"><?= h($s['phone']) ?></a>
    </div>
<?php endif; ?>
<?php if ($s['email']): ?>
    <div class="contact-card">
      <p class="footer-title dark">Email</p>
      <a class="contact-big" href="mailto:<?= h($s['email']) ?>"><?= h($s['email']) ?></a>
    </div>
<?php endif; ?>
<?php if ($s['address']): ?>
    <div class="contact-card">
      <p class="footer-title dark">Visit</p>
      <p class="contact-big"><?= h($s['address']) ?></p>
      <a class="link-arrow" href="<?= h($map) ?>" target="_blank" rel="noopener">Open in Maps &rarr;</a>
    </div>
<?php endif; ?>
<?php if ($s['hours']): ?>
    <div class="contact-card">
      <p class="footer-title dark">Hours</p>
      <ul class="hours">
<?php foreach ($s['hours'] as $line): ?>        <li><?= h((string)$line) ?></li>
<?php endforeach; ?>
      </ul>
    </div>
<?php endif; ?>
  </div>
</section>
<?php page_footer(); ?>
